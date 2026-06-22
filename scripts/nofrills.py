from base import save_deals
from flipp import scrape

MERCHANT = "frills"
STORE_URL = "https://www.nofrills.ca/en/deals/flyer"
STORE_NAME = "No Frills"


def run() -> None:
    deals = scrape(MERCHANT, STORE_URL, STORE_NAME)
    save_deals(deals)


if __name__ == "__main__":
    print(f"=== {STORE_NAME} ===")
    run()
