"""
Al Premium scraper — flyer is published as images on a Shopify page.
Claude Vision reads each page and extracts structured deal data.
"""

import base64
import html
import json
import os
import re
import urllib.request

import anthropic

from base import HEADERS, save_deals

PAGE_URL = "https://alpremium.ca/pages/weekly-special-eglinton.json"
STORE_URL = "https://alpremium.ca/pages/weekly-special-eglinton"
STORE_NAME = "Al Premium"


def fetch_flyer_image_urls() -> tuple[list[str], str, str]:
    req = urllib.request.Request(PAGE_URL, headers=HEADERS)
    with urllib.request.urlopen(req, timeout=15) as r:
        data = json.loads(r.read())

    body = html.unescape(data["page"]["body_html"])
    image_urls = re.findall(r'src="(https://cdn\.shopify\.com/[^"]+\.jpg)"', body)

    # Try to extract validity dates from the image filename (e.g. Jun18-24_2026)
    valid_from, valid_to = None, None
    if image_urls:
        m = re.search(r'(\w{3})(\d{1,2})-(\d{1,2})_(\d{4})', image_urls[0])
        if m:
            month, day_from, day_to, year = m.groups()
            valid_from = f"{year}-{_month_num(month):02d}-{int(day_from):02d}"
            valid_to   = f"{year}-{_month_num(month):02d}-{int(day_to):02d}"

    return image_urls, valid_from, valid_to


def _month_num(abbr: str) -> int:
    months = {"jan":1,"feb":2,"mar":3,"apr":4,"may":5,"jun":6,
              "jul":7,"aug":8,"sep":9,"oct":10,"nov":11,"dec":12}
    return months.get(abbr.lower(), 1)


def download_image_b64(url: str) -> str:
    req = urllib.request.Request(url, headers=HEADERS)
    with urllib.request.urlopen(req, timeout=30) as r:
        return base64.standard_b64encode(r.read()).decode("utf-8")


def extract_deals_from_image(
    client: anthropic.Anthropic,
    image_b64: str,
    page_num: int,
) -> list[dict]:
    response = client.messages.create(
        model="claude-opus-4-8",
        max_tokens=2048,
        messages=[{
            "role": "user",
            "content": [
                {
                    "type": "image",
                    "source": {
                        "type": "base64",
                        "media_type": "image/jpeg",
                        "data": image_b64,
                    },
                },
                {
                    "type": "text",
                    "text": (
                        "This is page " + str(page_num) + " of a grocery store flyer. "
                        "Extract every product deal visible on this page. "
                        "For each item return a JSON object with these fields:\n"
                        "- title: product name including size/weight/quantity (e.g. 'Chicken Breast 2 kg')\n"
                        "- price: sale/current price as a plain number string (e.g. '5.99')\n"
                        "- regular_price: original/regular price if shown (e.g. '8.99'), or empty string\n"
                        "- unit_price: per-unit or per-kg price if shown (e.g. '$3.99/lb'), or empty string\n\n"
                        "Respond ONLY with a JSON array. No markdown, no explanation, no code fences. "
                        "If no deals are visible, return an empty array []."
                    ),
                },
            ],
        }],
    )

    text = next((b.text for b in response.content if b.type == "text"), "[]")
    text = re.sub(r"^```[a-z]*\n?", "", text.strip())
    text = re.sub(r"\n?```$", "", text)
    try:
        return json.loads(text)
    except json.JSONDecodeError:
        print(f"  Warning: could not parse Claude response for page {page_num}")
        return []


def run() -> None:
    api_key = os.getenv("ANTHROPIC_API_KEY")
    if not api_key:
        raise RuntimeError("ANTHROPIC_API_KEY is not set")

    client = anthropic.Anthropic(api_key=api_key)

    image_urls, valid_from, valid_to = fetch_flyer_image_urls()
    print(f"  Found {len(image_urls)} flyer pages  ({valid_from} → {valid_to})")

    all_deals = []
    for i, url in enumerate(image_urls, start=1):
        print(f"  Reading page {i}/{len(image_urls)}...")
        image_b64 = download_image_b64(url)
        items = extract_deals_from_image(client, image_b64, i)
        print(f"    Extracted {len(items)} deals")

        for item in items:
            title = (item.get("title") or "").strip()
            price = (item.get("price") or "").strip()
            if not title or not price:
                continue
            all_deals.append({
                "title": title,
                "price": price,
                "regular_price": (item.get("regular_price") or "").strip(),
                "unit_price": (item.get("unit_price") or "").strip(),
                "category": "",
                "valid_from": valid_from,
                "valid_to": valid_to,
                "url": STORE_URL,
            })

    print(f"  Total deals extracted: {len(all_deals)}")
    save_deals(all_deals)


if __name__ == "__main__":
    print(f"=== {STORE_NAME} ===")
    run()
