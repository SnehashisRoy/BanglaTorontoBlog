import json
import os
import random
import urllib.request
import mysql.connector

DB = dict(
    host=os.getenv("DB_HOST", "127.0.0.1"),
    port=int(os.getenv("DB_PORT", 3306)),
    database=os.getenv("DB_DATABASE", "laravel"),
    user=os.getenv("DB_USERNAME", "laravel"),
    password=os.getenv("DB_PASSWORD", "secret"),
)

FLYER_URL = "https://www.nofrills.ca/en/deals/flyer"
POSTAL_CODE = "M5H2N2"  # Downtown Toronto — change to match your target area

HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
    ),
    "Accept": "application/json",
    "Origin": "https://flipp.com",
    "Referer": "https://flipp.com/",
}


def get(url: str) -> dict | list:
    req = urllib.request.Request(url, headers=HEADERS)
    with urllib.request.urlopen(req, timeout=15) as r:
        return json.loads(r.read())


def get_nofrills_flyer_id() -> tuple[int, dict]:
    sid = random.randint(1000000000000000, 9999999999999999)
    url = (
        f"https://dam.flippenterprise.net/api/flipp/data"
        f"?locale=en&postal_code={POSTAL_CODE}&sid={sid}"
    )
    data = get(url)
    flyers = data.get("flyers", [])
    for flyer in flyers:
        if "frills" in flyer.get("merchant", "").lower():
            return flyer["id"], flyer
    raise RuntimeError(f"No Frills flyer not found near postal code {POSTAL_CODE}")


def fetch_flyer_items(flyer_id: int) -> list[dict]:
    url = f"https://dam.flippenterprise.net/api/flipp/flyers/{flyer_id}/flyer_items"
    return get(url)


def parse_deals(items: list[dict], flyer_meta: dict) -> list[dict]:
    deals = []
    for item in items:
        name = (item.get("name") or "").strip()
        price = (item.get("price") or "").strip()
        if not name or not price:
            continue
        deals.append({
            "title": name,
            "price": item.get("price") or "",
            "regular_price": "",
            "unit_price": "",
            "category": ", ".join(flyer_meta.get("categories", [])),
            "valid_from": (item.get("valid_from") or "")[:10] or None,
            "valid_to": (item.get("valid_to") or "")[:10] or None,
            "url": FLYER_URL,
        })
    return deals


def save_deals(deals: list[dict]) -> None:
    if not deals:
        print("No deals to save.")
        return

    conn = mysql.connector.connect(**DB)
    cursor = conn.cursor()
    saved = 0
    for deal in deals:
        cursor.execute(
            """
            INSERT INTO deals (title, price, regular_price, unit_price, category,
                               valid_from, valid_to, url, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                price        = VALUES(price),
                valid_from   = VALUES(valid_from),
                valid_to     = VALUES(valid_to),
                updated_at   = NOW()
            """,
            (
                deal["title"], deal["price"], deal["regular_price"],
                deal["unit_price"], deal["category"],
                deal["valid_from"], deal["valid_to"], deal["url"],
            ),
        )
        saved += cursor.rowcount
    conn.commit()
    cursor.close()
    conn.close()
    print(f"Saved/updated {saved} deals.")


if __name__ == "__main__":
    print("Fetching No Frills flyer from Flipp...")
    flyer_id, flyer_meta = get_nofrills_flyer_id()
    print(f"Found flyer: {flyer_meta['name']} (id={flyer_id})")
    print(f"Valid: {flyer_meta['valid_from'][:10]} → {flyer_meta['valid_to'][:10]}")

    items = fetch_flyer_items(flyer_id)
    print(f"Fetched {len(items)} items")

    deals = parse_deals(items, flyer_meta)
    print(f"Parsed {len(deals)} deals")
    print("Sample:", json.dumps(deals[:2], indent=2))

    save_deals(deals)
