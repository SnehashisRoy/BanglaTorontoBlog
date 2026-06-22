from base import save_deals
from flipp import scrape

MERCHANT = "superstore"
STORE_URL = "https://www.realcanadiansuperstore.ca/en/deals/flyer"
STORE_NAME = "Real Canadian Superstore"


def run() -> None:
    deals = scrape(MERCHANT, STORE_URL, STORE_NAME)
    save_deals(deals)


if __name__ == "__main__":
    print(f"=== {STORE_NAME} ===")
    run()
