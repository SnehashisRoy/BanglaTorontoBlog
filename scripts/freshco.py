from base import save_deals
from flipp import scrape

MERCHANT = "freshco"
STORE_URL = "https://www.freshco.com/en/deals/flyer"
STORE_NAME = "FreshCo"


def run() -> None:
    deals = scrape(MERCHANT, STORE_URL, STORE_NAME)
    save_deals(deals)


if __name__ == "__main__":
    print(f"=== {STORE_NAME} ===")
    run()
