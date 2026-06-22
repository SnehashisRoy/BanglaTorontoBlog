import json
import os
import urllib.request

import mysql.connector

POSTAL_CODE = os.getenv("POSTAL_CODE", "M5H2N2")

DB = dict(
    host=os.getenv("DB_HOST", "127.0.0.1"),
    port=int(os.getenv("DB_PORT", 3306)),
    database=os.getenv("DB_DATABASE", "laravel"),
    user=os.getenv("DB_USERNAME", "laravel"),
    password=os.getenv("DB_PASSWORD", "secret"),
)

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


def save_deals(deals: list[dict]) -> None:
    if not deals:
        print("  No deals to save.")
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
                price      = VALUES(price),
                valid_from = VALUES(valid_from),
                valid_to   = VALUES(valid_to),
                updated_at = NOW()
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
    print(f"  Saved/updated {saved} deals.")
