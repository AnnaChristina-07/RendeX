import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

def run_automation():
    print("Setting up WebDriver...")
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service)
    
    wait = WebDriverWait(driver, 15)

    try:
        base_url = "http://localhost/RendeX"

        # 1. Go to Home Page
        print("Navigating to Home Page...")
        driver.get(f"{base_url}/index.php")
        driver.maximize_window()

        # 2. Login Process
        print("Logging in with Admin credentials...")
        login_link = wait.until(EC.element_to_be_clickable((By.LINK_TEXT, "Login")))
        login_link.click()

        email_input = wait.until(EC.presence_of_element_located((By.NAME, "email")))
        password_input = driver.find_element(By.NAME, "password")
        
        email_input.send_keys("annachristina2005@gmail.com")
        password_input.send_keys("anna@2005")

        login_button = driver.find_element(By.XPATH, "//button[contains(text(), 'LOG IN')]")
        login_button.click()

        # 3. Go to Admin Dashboard
        print("Waiting for Admin Dashboard to load...")
        wait.until(EC.url_contains("admin_dashboard.php"))
        
        # 4. Go to Deliveries Section
        print("Navigating to Deliveries section...")
        deliveries_tab = wait.until(EC.element_to_be_clickable(
            (By.XPATH, "//a[contains(@href, '?tab=deliveries')]")
        ))
        deliveries_tab.click()

        # Wait for deliveries tab to load
        wait.until(EC.url_contains("tab=deliveries"))
        
        # 5. Search for Delivery ID
        delivery_id = "del_699693d3d1adb"
        print(f"Searching for delivery ID: {delivery_id}")
        
        # Locate the search bar and input the ID
        search_input = wait.until(EC.presence_of_element_located((By.NAME, "search_delivery")))
        search_input.clear()
        search_input.send_keys(delivery_id)
        
        # Click the search button
        search_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Search')]")
        search_btn.click()
        
        # Wait for the search results to load (wait for the Timeline View header)
        wait.until(EC.presence_of_element_located((By.XPATH, "//h3[contains(text(), 'Search Result Details')]")))
        
        # 6. Extract the details
        print("\n--- Delivery Details Extracted via Selenium ---")
        
        # Extract Item Name
        item_name_element = wait.until(EC.presence_of_element_located((By.XPATH, "//h4[contains(@class, 'text-xl font-bold')]")))
        print(f"Rental Item: {item_name_element.text.strip()}")
        
        # Extract Total Price
        total_price_element = wait.until(EC.presence_of_element_located((By.XPATH, "//span[contains(@class, 'text-2xl font-black')]")))
        print(f"Total Price: {total_price_element.text.strip()}")
        
        # Log active delivery status step
        active_status = wait.until(EC.presence_of_element_located((By.XPATH, "//div[contains(@class, 'bg-black text-white') and contains(@class, 'ring-4')]//following-sibling::span")))
        print(f"Status     : {active_status.text.strip()}")
        
        print("-----------------------------------------------\n")
        
        time.sleep(3)
        print("Automation Script Finished Successfully!")

    except Exception as e:
        print(f"Test Failed with Exception: {e}")

    finally:
        print("Closing the browser...")
        driver.quit()

if __name__ == "__main__":
    run_automation()
