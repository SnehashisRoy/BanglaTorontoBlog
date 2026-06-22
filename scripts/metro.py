from base import save_deals
from flipp import scrape

MERCHANT = "metro"
STORE_URL = "https://www.metro.ca/en/flyer"
STORE_NAME = "Metro"


def run() -> None:
    deals = scrape(MERCHANT, STORE_URL, STORE_NAME)
    save_deals(deals)


if __name__ == "__main__":
    print(f"=== {STORE_NAME} ===")
    run()
