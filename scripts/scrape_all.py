import sys

import alpremium
import foodbasics
import freshco
import metro
import nofrills
import superstore

SCRAPERS = [
    ("No Frills", nofrills.run),
    ("FreshCo", freshco.run),
    ("Metro", metro.run),
    ("Food Basics", foodbasics.run),
    ("Real Canadian Superstore", superstore.run),
    ("Al Premium", alpremium.run),
]

errors = []

for name, run in SCRAPERS:
    print(f"\n=== {name} ===")
    try:
        run()
    except Exception as exc:
        print(f"  ERROR: {exc}")
        errors.append((name, exc))

print(f"\n{'='*40}")
print(f"Done. {len(SCRAPERS) - len(errors)}/{len(SCRAPERS)} stores succeeded.")

if errors:
    for name, exc in errors:
        print(f"  FAILED: {name} — {exc}")
    sys.exit(1)
