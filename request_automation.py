import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
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
        print("Logging in...")
        login_link = wait.until(EC.element_to_be_clickable((By.LINK_TEXT, "Login")))
        login_link.click()

        email_input = wait.until(EC.presence_of_element_located((By.NAME, "email")))
        password_input = driver.find_element(By.NAME, "password")
        
        email_input.send_keys("agnussabu2028@mca.ajce.in")
        password_input.send_keys("agnus@2005")

        login_button = driver.find_element(By.XPATH, "//button[contains(text(), 'LOG IN')]")
        login_button.click()

        # 3. Go to Dashboard
        print("Waiting for Dashboard to load...")
        wait.until(EC.url_contains("dashboard.php"))
        
        # 4. Go to My Requests page
        print("Navigating to My Requests...")
        my_requests_link = wait.until(EC.element_to_be_clickable((By.LINK_TEXT, "My Requests")))
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", my_requests_link)
        time.sleep(1)
        my_requests_link.click()
        
        # Wait for My Requests page to load
        wait.until(EC.url_contains("my_requests.php"))
        
        # 5. Click on New Request
        print("Clicking on New Request...")
        new_request_btn = wait.until(EC.element_to_be_clickable(
            (By.XPATH, "//a[contains(text(), 'New Request') or contains(., 'New Request')]")
        ))
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", new_request_btn)
        time.sleep(1)
        new_request_btn.click()
        
        # Wait for Request Item page to load
        wait.until(EC.url_contains("request-item.php"))
        
        # 6. Enter data in form
        print("Filling out the request form...")
        item_name_input = wait.until(EC.presence_of_element_located((By.NAME, "item_name")))
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", item_name_input)
        
        item_name_input.send_keys("Acoustic Guitar for weekend trip")
        
        location_input = driver.find_element(By.NAME, "location")
        location_input.send_keys("Central Park Area")
        
        # Select category
        category_select = Select(driver.find_element(By.NAME, "category"))
        category_select.select_by_value("student-essentials")
        
        description_input = driver.find_element(By.NAME, "description")
        description_input.send_keys("Looking for a well-maintained acoustic guitar for a 3-day weekend trip. Willing to pick it up.")
        
        min_price_input = driver.find_element(By.NAME, "min_price")
        min_price_input.send_keys("100")
        
        max_price_input = driver.find_element(By.NAME, "max_price")
        max_price_input.send_keys("500")
        
        # 7. Submit request
        print("Submitting request...")
        submit_btn = driver.find_element(By.XPATH, "//button[@type='submit']")
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", submit_btn)
        time.sleep(1)
        submit_btn.click()
        
        # 8. Verify submission
        print("Waiting for redirection after submission...")
        wait.until(EC.url_contains("dashboard.php?msg=request_submitted"))
        
        time.sleep(3)
        print("Request Automation Script Finished Successfully!")

    except Exception as e:
        print(f"Test Failed with Exception: {e}")

    finally:
        print("Closing the browser...")
        driver.quit()

if __name__ == "__main__":
    run_automation()
