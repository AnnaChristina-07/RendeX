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
    
    # Increase maximum wait time to account for possible slow loading elements
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

        # Wait for login fields
        email_input = wait.until(EC.presence_of_element_located((By.NAME, "email")))
        password_input = driver.find_element(By.NAME, "password")
        
        email_input.send_keys("agnussabu2028@mca.ajce.in")
        password_input.send_keys("agnus@2005")

        login_button = driver.find_element(By.XPATH, "//button[contains(text(), 'LOG IN')]")
        login_button.click()

        # 3. Go to Dashboard
        print("Waiting for Dashboard to load...")
        wait.until(EC.url_contains("dashboard.php"))
        
        # 4. Click on 'Student Essentials' from 'Browse by Category'
        print("Selecting 'Student Essentials' category...")
        student_essentials = wait.until(EC.presence_of_element_located(
            (By.XPATH, "//span[contains(normalize-space(text()), 'Student Essentials')]/parent::a")
        ))
        
        # Use javascript scroll to avoid intercepted click exceptions
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", student_essentials)
        time.sleep(1) # Small animation sleep
        student_essentials.click()

        # Wait for category page to be loaded
        wait.until(EC.url_contains("category.php"))

        # 5. Select calculator
        print("Searching and selecting calculator...")
        calculator_item = wait.until(EC.presence_of_element_located(
            # Find an anchor tag traversing up from the H3 title which contains the word 'calculator'
            (By.XPATH, "//h3[contains(translate(text(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'calculator')]/ancestor::a")
        ))
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", calculator_item)
        time.sleep(1)
        calculator_item.click()

        # 6. Click on Rent Now button
        print("Clicking Rent Now...")
        wait.until(EC.url_contains("item-details.php"))
        rent_now_button = wait.until(EC.element_to_be_clickable((By.NAME, "rent_now")))
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", rent_now_button)
        time.sleep(1)
        rent_now_button.click()

        # Wait for the confirm rental page
        print("Waiting for Confirm Rental page...")
        wait.until(EC.url_contains("confirm-rental.php"))

        # 7. Click on Self Pick Up
        print("Selecting Self Pick Up...")
        self_pick_up_btn = wait.until(EC.element_to_be_clickable((By.ID, "btn-pickup")))
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", self_pick_up_btn)
        time.sleep(1)
        self_pick_up_btn.click()

        # 8. Click on Proceed to Payment
        print("Proceeding to Payment section...")
        proceed_button = wait.until(EC.element_to_be_clickable(
            (By.XPATH, "//button[contains(text(), 'Proceed to Payment') or contains(., 'Proceed to Payment')]")
        ))
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", proceed_button)
        time.sleep(1)
        proceed_button.click()

        # 9. Conduct Payment
        print("Clicking Final Pay Now button...")
        # Step 2 container must be visible before clicking on payment confirm
        pay_now_btn = wait.until(EC.element_to_be_clickable((By.NAME, "confirm_booking")))
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", pay_now_btn)
        time.sleep(1)
        pay_now_btn.click()

        print("Payment triggered. Waiting for Razorpay modal...")
        time.sleep(3) 

        # --- OPTIONAL: Razorpay Test Frame Interactivity ---
        # NOTE: Handling the Razorpay modal via Selenium requires switching iframes. 
        # If you are strictly simulating a 'success' interaction via a dummy test mode UI, uncomment below:
        """
        try:
            wait.until(EC.frame_to_be_available_and_switch_to_it((By.CSS_SELECTOR, ".razorpay-checkout-frame")))
            
            # Example Test Action on Razorpay (Clicking "Netbanking" -> "SBI" -> "Pay")
            net_banking = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(., 'Netbanking')]")))
            net_banking.click()
            
            sbi = wait.until(EC.element_to_be_clickable((By.XPATH, "//div[contains(text(), 'SBI')]")))
            sbi.click()
            
            pay_btn = wait.until(EC.element_to_be_clickable((By.ID, "footer-cta")))
            pay_btn.click()
            
            # Switch to final popup window
            driver.switch_to.window(driver.window_handles[-1])
            success_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Success')]")))
            success_btn.click()
            
            # Return to main window
            driver.switch_to.window(driver.window_handles[0])
            print("Completed simulated Razorpay checkout.")
        except Exception as e:
            print("Could not interact with Razorpay modal programmatically: ", e)
        """

        # Wait a few seconds to visually confirm actions
        time.sleep(5)
        print("Automation Script Finished Successfully!")

    except Exception as e:
        print(f"Test Failed with Exception: {e}")

    finally:
        print("Closing the browser...")
        driver.quit()

if __name__ == "__main__":
    run_automation()
