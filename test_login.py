import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

def test_rendex_login():
    # 1. Setup the Chrome WebDriver
    print("Initializing WebDriver...")
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service)
    
    try:
        # Define the base URL (adjust if your local port is different, e.g., localhost:8080)
        base_url = "http://localhost/RendeX"

        # 2. Go to Home Page
        print("Navigating to Home Page...")
        driver.get(f"{base_url}/index.php")
        driver.maximize_window()
        
        # 3. Find and click the 'Login' link in the navigation bar
        print("Clicking Login...")
        wait = WebDriverWait(driver, 10)
        login_link = wait.until(EC.element_to_be_clickable((By.LINK_TEXT, "Login")))
        login_link.click()

        # 4. Wait for the login page to load and locate the form inputs
        print("Waiting for Login Page...")
        email_input = wait.until(EC.presence_of_element_located((By.NAME, "email")))
        password_input = driver.find_element(By.NAME, "password")
        login_button = driver.find_element(By.XPATH, "//button[contains(text(), 'LOG IN')]")

        # 5. Enter Credentials
        print("Entering credentials...")
        email_input.send_keys("agnussabu2028@mca.ajce.in")
        password_input.send_keys("agnus@2005")

        # 6. Click Login
        print("Submitting the form...")
        login_button.click()

        # 7. Check if redirected to the Dashboard successfully
        print("Waiting for redirection to the dashboard...")
        # Since RendeX has multiple dashboard roles, we just ensure "dashboard.php" is in the URL
        wait.until(EC.url_contains("dashboard.php"))
        
        current_url = driver.current_url
        if "dashboard.php" in current_url:
            print(f"✅ TEST PASSED: Successfully logged in and redirected to {current_url}")
        else:
            print(f"❌ TEST FAILED: Redirected to unexpected URL: {current_url}")
            
        # Optional: Let the browser stay open for a few seconds so you can visually verify
        time.sleep(3)

    except Exception as e:
        print(f"❌ TEST FAILED with Exception: {str(e)}")
        
    finally:
        # Close the browser
        print("Closing the browser...")
        driver.quit()

if __name__ == "__main__":
    test_rendex_login()
