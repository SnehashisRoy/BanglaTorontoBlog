"""Shared Flipp API helpers used by all Flipp-based store scrapers."""

import random

from base import POSTAL_CODE, get


def find_flyer(merchant_keyword: str) -> tuple[int, dict]:
    sid = random.randint(1_000_000_000_000_000, 9_999_999_999_999_999)
    url = (
        f"https://dam.flippenterprise.net/api/flipp/data"
        f"?locale=en&postal_code={POSTAL_CODE}&sid={sid}"
    )
    data = get(url)
    for flyer in data.get("flyers", []):
        if merchant_keyword.lower() in flyer.get("merchant", "").lower():
            return flyer["id"], flyer
    raise RuntimeError(
        f"No flyer found for merchant '{merchant_keyword}' near {POSTAL_CODE}"
    )


def fetch_items(flyer_id: int) -> list[dict]:
    return get(
        f"https://dam.flippenterprise.net/api/flipp/flyers/{flyer_id}/flyer_items"
    )


def parse_deals(items: list[dict], flyer_meta: dict, store_url: str) -> list[dict]:
    deals = []
    for item in items:
        name = (item.get("name") or "").strip()
        price = (item.get("price") or "").strip()
        if not name or not price:
            continue
        deals.append({
            "title": name,
            "price": price,
            "regular_price": "",
            "unit_price": "",
            "category": ", ".join(flyer_meta.get("categories", [])),
            "valid_from": (item.get("valid_from") or "")[:10] or None,
            "valid_to": (item.get("valid_to") or "")[:10] or None,
            "url": store_url,
        })
    return deals


def scrape(merchant_keyword: str, store_url: str, store_name: str) -> list[dict]:
    print(f"  Fetching flyer from Flipp...")
    flyer_id, flyer_meta = find_flyer(merchant_keyword)
    valid_from = (flyer_meta.get("valid_from") or "")[:10]
    valid_to = (flyer_meta.get("valid_to") or "")[:10]
    print(f"  Found: {flyer_meta.get('name')} (id={flyer_id}, {valid_from} → {valid_to})")
    items = fetch_items(flyer_id)
    deals = parse_deals(items, flyer_meta, store_url)
    print(f"  Parsed {len(deals)} deals")
    return deals
