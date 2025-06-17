let cart = []
let currentSection = "home"
let isLoggedIn = false
let currentUser = null
let deliveryMethod = "pickup"
let selectedSize = "Grande"
let currentProduct = null
let lastDrinkType = 'cold'; // Track last selected filter

// Navigation
function showSection(sectionName) {
  document.querySelectorAll(".section-content").forEach((section) => {
    section.style.display = "none"
    section.classList.remove("active")
  })
  const targetSection = document.getElementById(sectionName)
  if (targetSection) {
    targetSection.style.display = "block"
    targetSection.classList.add("active")
    // Always apply filter when showing products section
    if (sectionName === "products") {
      filterDrinks(lastDrinkType);
    }
  }
  document.querySelectorAll(".nav-item").forEach((item) => {
    item.classList.remove("active")
  })
  const clickedNavItem = Array.from(document.querySelectorAll(".nav-item")).find((item) => {
    const itemText = item.textContent.toLowerCase().trim()
    const targetText = sectionName.toLowerCase().trim()
    if (itemText === "menu" && targetText === "about") return true
    if (itemText === "shop" && targetText === "products") return true
    return itemText === targetText
  })
  if (clickedNavItem) {
    clickedNavItem.classList.add("active")
  }
  currentSection = sectionName
  window.scrollTo(0, 0)
}

// Product Modal Functions
function openProductModal(id, name, price, description, image) {
  if (!isLoggedIn) {
    showLoginModal();
    return;
  }
  currentProduct = { id, name, price, description, image };
  document.getElementById("modalProductName").textContent = name;
  document.getElementById("modalProductPrice").textContent = `Php ${price}`;
  document.getElementById("modalProductDescription").textContent = description;
  document.getElementById("modalProductImage").src = image;
  document.getElementById("modalProductImage").alt = name;
  selectedSize = "Grande";
  document.querySelectorAll(".size-btn").forEach((btn) => btn.classList.remove("active"));
  document.querySelector(".size-btn").classList.add("active");
  const modal = document.getElementById("productModal");
  modal.classList.add("active");
  modal.style.display = "flex";
  modal.style.alignItems = "center";
  modal.style.justifyContent = "center";
  modal.style.position = "fixed";
  modal.style.top = "0";
  modal.style.left = "0";
  modal.style.width = "100vw";
  modal.style.height = "100vh";
  modal.style.background = "rgba(0,0,0,0.15)";
  modal.style.zIndex = "3000";
  document.body.style.overflow = "hidden";

  // Ensure the yellow close button closes the modal
  const yellowCloseBtn = modal.querySelector('.product-modal-close-yellow');
  if (yellowCloseBtn) {
    yellowCloseBtn.onclick = function (e) {
      e.stopPropagation();
      closeProductModal();
    };
  }
}

function closeProductModal() {
  const modal = document.getElementById("productModal");
  if (modal) {
    modal.classList.remove("active");
    modal.style.display = "none"; // <-- Ensure modal is hidden
  }
  document.body.style.overflow = "auto";
  currentProduct = null;
}

function selectSize(size) {
  selectedSize = size;
  document.querySelectorAll(".size-btn").forEach((btn) => {
    btn.classList.remove("active");
    if (btn.textContent.trim() === size) btn.classList.add("active");
  });
}

function addProductToCart() {
  if (currentProduct) {
    const productName = `${currentProduct.name} (${selectedSize})`;
    // Store product_id as product_id for backend reference
    addToCart(currentProduct.id, productName, currentProduct.price, selectedSize);
    closeProductModal();
    showNotification("Product added to cart!", "success");
  }
}

// Cart functionality
function addToCart(product_id, name, price, size) {
  // Find by product_id, name, and size
  const existingItem = cart.find((item) =>
    item.product_id === product_id && item.name === name && item.size === size
  );
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.push({
      product_id: product_id, // for backend reference
      name: name,
      price: price,
      quantity: 1,
      size: size
    });
  }
  updateCartCount();
  updateCartDisplay();
  if (!currentProduct) {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Added!';
    button.style.background = "linear-gradient(135deg, #10B981, #059669)";
    setTimeout(() => {
      button.innerHTML = originalText;
      button.style.background = "";
    }, 1500);
  }
}

function removeFromCart(product_id, size) {
  cart = cart.filter((item) => !(item.product_id === product_id && item.size === size));
  updateCartCount();
  updateCartDisplay();
}

function updateQuantity(product_id, change, size) {
  const item = cart.find((item) => item.product_id === product_id && item.size === size);
  if (item) {
    item.quantity += change;
    if (item.quantity <= 0) {
      removeFromCart(product_id, size);
    } else {
      updateCartCount();
      updateCartDisplay();
    }
  }
}

function getTotalItems() {
  return cart.reduce((sum, item) => sum + item.quantity, 0);
}

// Helper to get cart key for current user
function getCartKey() {
  if (currentUser && currentUser.id) {
    return `cart_user_${currentUser.id}`;
  }
  return "cart_guest";
}

// Load cart from localStorage if available (per user)
function loadCart() {
  try {
    const key = getCartKey();
    const savedCart = localStorage.getItem(key);
    if (savedCart) {
      cart = JSON.parse(savedCart);
    } else {
      cart = [];
    }
  } catch {
    cart = [];
  }
}

// Save cart to localStorage (per user)
function saveCart() {
  const key = getCartKey();
  localStorage.setItem(key, JSON.stringify(cart));
}

// Update cart count and save cart after any change
function updateCartCount() {
  const totalItems = getTotalItems();
  document.getElementById("cartCount").textContent = totalItems;
  const modalCartCount = document.getElementById("cartCountModal");
  if (modalCartCount) {
    modalCartCount.textContent = totalItems;
  }
  saveCart();
}

function updateCartDisplay() {
  const cartItemsContainer = document.getElementById("cartItems");
  const cartTotalContainer = document.getElementById("cartTotal");
  if (cart.length === 0) {
    cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h4>Your cart is empty</h4>
                <p>Add some delicious coffee to get started!</p>
            </div>
        `;
    cartTotalContainer.innerHTML = "";
    document.getElementById("deliveryOptions").style.display = "none";
    return;
  }
  cartItemsContainer.innerHTML = cart
    .map(
      (item) => `
        <div class="cart-item">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">₱${item.price.toFixed(2)} each</div>
            </div>
            <div class="quantity-controls">
                <button class="quantity-btn" onclick="updateQuantity('${item.product_id}', -1, '${item.size}')">-</button>
                <span class="quantity">${item.quantity}</span>
                <button class="quantity-btn" onclick="updateQuantity('${item.product_id}', 1, '${item.size}')">+</button>
            </div>
            <button class="remove-item" onclick="removeFromCart('${item.product_id}', '${item.size}')">Remove</button>
        </div>
    `
    )
    .join("");
  const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  cartTotalContainer.innerHTML = `
        <div class="total-amount">Total: ₱${total.toFixed(2)}</div>
        <button class="checkout-btn" onclick="handleCheckout()">
            <i class="fas fa-credit-card"></i> Checkout
        </button>
    `;
}

function openCart() {
  updateCartDisplay();
  document.getElementById("cartModal").classList.add("active");
  document.body.style.overflow = "hidden";
}

function closeCart() {
  document.getElementById("cartModal").classList.remove("active");
  document.body.style.overflow = "auto";
  document.getElementById("deliveryOptions").style.display = "none";
  deliveryMethod = "pickup";
}

// Delivery Functions
function startCheckout() {
  if (cart.length === 0) return;
  let deliveryOptions = document.getElementById("deliveryOptions");
  if (deliveryOptions) {
    deliveryOptions.style.display = "block";
    return;
  }
  deliveryOptions = document.createElement("div");
  deliveryOptions.id = "deliveryOptions";
  deliveryOptions.className = "delivery-options";
  deliveryOptions.style.marginTop = "20px";
  // Only show pickup form (no delivery)
  deliveryOptions.innerHTML = `
    <h4 style="font-size:1.35rem;font-weight:800;color:#40534b;margin-bottom:18px;">
      Pickup Details
    </h4>
    <form id="pickupForm" style="margin-bottom:0;">
      <div class="form-group" style="margin-bottom:12px;">
        <label style="font-weight:600;">Pickup Name</label>
        <input type="text" id="pickupName" placeholder="Enter your name" required style="width:100%;padding:10px;border-radius:8px;border:1.5px solid #e5e7eb;">
      </div>
      <div class="form-group" style="margin-bottom:12px;">
        <label style="font-weight:600;">Pickup Location</label>
        <input type="text" id="pickupLocation" value="123 Coffee Street, Downtown District, City, State 12345" required style="width:100%;padding:10px;border-radius:8px;border:1.5px solid #e5e7eb;">
      </div>
      <div class="form-group" style="margin-bottom:12px;">
        <label style="font-weight:600;">Pickup Time</label>
        <input type="time" id="pickupTime" required style="width:100%;padding:10px;border-radius:8px;border:1.5px solid #e5e7eb;">
        <p id="pickupTimeNote" style="margin-top:6px;font-size:0.95em;color:#b45309;">Note: We are open only 3:00 p.m to 9:00 p.m. Thank you!</p>
      </div>
      <div class="form-group" style="margin-bottom:18px;">
        <label style="font-weight:600;">Special Instructions (Optional)</label>
        <input type="text" id="specialInstructions" placeholder="e.g. Please call when outside" style="width:100%;padding:10px;border-radius:8px;border:1.5px solid #e5e7eb;">
      </div>
    </form>
  `;
  // Insert after cartItems, before cartTotal
  const cartContent = document.querySelector(".cart-content");
  const cartTotal = document.getElementById("cartTotal");
  if (cartContent && cartTotal) {
    cartContent.insertBefore(deliveryOptions, cartTotal);
  } else if (cartContent) {
    cartContent.appendChild(deliveryOptions);
  }

  // Ensure DOM is ready before attaching event
  setTimeout(() => {
    const pickupTimeInput = document.getElementById("pickupTime");
    const note = document.getElementById("pickupTimeNote");
    if (pickupTimeInput && note) {
      pickupTimeInput.min = "15:00";
      pickupTimeInput.max = "21:00";
      pickupTimeInput.addEventListener("input", function () {
        const val = this.value;
        if (!val) {
          note.textContent = "Note: We are open only 3:00 p.m to 9:00 p.m. Thank you!";
          note.style.color = "#b45309";
          this.setCustomValidity("");
          return;
        }
        const [h, m] = val.split(":").map(Number);
        const mins = h * 60 + m;
        if (mins < 15 * 60 || mins > 21 * 60) {
          note.textContent = "Please select a time between 3:00 p.m and 9:00 p.m.";
          note.style.color = "#dc2626";
          this.setCustomValidity("Pickup time must be between 3:00 p.m and 9:00 p.m.");
        } else {
          note.textContent = "Note: We are open only 3:00 p.m to 9:00 p.m. Thank you!";
          note.style.color = "#b45309";
          this.setCustomValidity("");
        }
      });
    }
  }, 0);
}

// Call this function when the user submits the pickup form
function completePickupCheckout() {
  const pickup_name = document.getElementById("pickupName").value;
  const pickup_location = document.getElementById("pickupLocation").value;
  const pickup_time = document.getElementById("pickupTime").value;
  const special_instructions = document.getElementById("specialInstructions").value;

  fetch('validations/pickup_checkout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `pickup_name=${encodeURIComponent(pickup_name)}&pickup_location=${encodeURIComponent(pickup_location)}&pickup_time=${encodeURIComponent(pickup_time)}&special_instructions=${encodeURIComponent(special_instructions)}`
  })
    .then(res => {
      // Debug: log the status and response
      console.log('Pickup checkout response status:', res.status);
      return res.text().then(text => {
        console.log('Pickup checkout raw response:', text);
        try {
          return JSON.parse(text);
        } catch (e) {
          showNotification("Invalid server response.", "error");
          throw e;
        }
      });
    })
    .then(data => {
      if (data.success) {
        showNotification("Pickup order placed successfully!", "success");
        closeCart();
        cart = [];
        updateCartCount();
        updateCartDisplay();
      } else {
        showNotification(data.message || "Pickup order failed.", "error");
      }
    })
    .catch((err) => {
      console.error('Pickup checkout error:', err);
      showNotification("Network error. Please try again.", "error");
    });
}

// Auth functions
function showAuthModal() {
  if (isLoggedIn) {
    // Remove this user's cart from localStorage and clear cart
    if (currentUser && currentUser.id) {
      localStorage.removeItem(getCartKey());
    }
    currentUser = null;
    cart = [];
    updateCartCount();
    updateCartDisplay();
    logout();
  } else {
    showLoginModal();
  }
}

function showLoginModal() {
  document.getElementById("loginModal").classList.add("active");
  document.body.style.overflow = "hidden";
}

function showRegisterModal() {
  document.getElementById("registerModal").classList.add("active");
  document.body.style.overflow = "hidden";
}

function closeAuthModal() {
  document.getElementById("loginModal").classList.remove("active");
  document.getElementById("registerModal").classList.remove("active");
  document.body.style.overflow = "auto";
  document.querySelectorAll(".auth-form").forEach((form) => form.reset());
  document.querySelectorAll(".success-message").forEach((msg) => msg.classList.remove("show"));
  document.querySelectorAll(".auth-btn").forEach((btn) => {
    btn.classList.remove("loading");
    btn.disabled = false;
  });
}

function switchToRegister() {
  document.getElementById("loginModal").classList.remove("active");
  document.getElementById("registerModal").classList.add("active");
}

function switchToLogin() {
  document.getElementById("registerModal").classList.remove("active");
  document.getElementById("loginModal").classList.add("active");
}

function handleLogin(event) {
    event.preventDefault();
    var fullname = document.getElementById('loginFullname').value;
    var password = document.getElementById('loginPassword').value;
    var loginBtn = document.getElementById('loginBtn');
    loginBtn.disabled = true;
    loginBtn.classList.add("loading");

    fetch('logging/login.php', {
        method: 'POST',
        body: new URLSearchParams({
            action: 'login',
            fullname: fullname,
            password: password
        }),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    })
    .then(res => res.json())
    .then(data => {
        loginBtn.disabled = false;
        loginBtn.classList.remove("loading");
        if (data.success) {
            window.PHP_IS_LOGGED_IN = true; // <-- Ensure JS login state is updated
            if (data.is_admin && data.redirect) {
                window.location.href = data.redirect; // <-- Use backend-provided redirect
            } else {
                document.getElementById("profileText").textContent = data.firstName;
                document.getElementById("profileAvatar").innerHTML = data.initials;
                let navbarUser = document.querySelector(".navbar-username");
                if (navbarUser) navbarUser.textContent = data.fullname;
                document.getElementById("loginSuccess").classList.add("show");
                setTimeout(() => {
                    closeAuthModal();
                    location.reload();
                }, 1000);
            }
        } else {
            document.getElementById('loginFullnameError').textContent = data.message || 'Login failed.';
        }
    })
    .catch(() => {
        loginBtn.disabled = false;
        loginBtn.classList.remove("loading");
        document.getElementById('loginFullnameError').textContent = 'Login failed. Please try again.';
    });
}




// --- Sync logout with session ---
function logout() {
  fetch("logging/logout.php", { method: "POST" })
    .then(() => {
      isLoggedIn = false;
      // Remove only this user's cart from localStorage
      if (currentUser && currentUser.id) {
        localStorage.removeItem(getCartKey());
      }
      currentUser = null;
      cart = [];
      updateCartCount();
      updateCartDisplay();
      document.getElementById("profileText").textContent = "Sign In";
      document.getElementById("profileAvatar").innerHTML = '<i class="fas fa-user"></i>';
      let navbarUser = document.querySelector(".navbar-username");
      if (navbarUser) navbarUser.textContent = "";
      closeAuthModal();
      showNotification("Successfully signed out!", "success");
      // Update dropdown menu to show only Sign In
      if (typeof updateProfileDropdownMenu === "function") {
        updateProfileDropdownMenu(false);
      }
    });
}

// Registration via AJAX (optional, fallback to PHP if needed)
function handleRegister(event) {
  event.preventDefault();
  const name = document.getElementById("registerName").value;
  const email = document.getElementById("registerEmail").value;
  const password = document.getElementById("registerPassword").value;
  const confirmPassword = document.getElementById("confirmPassword").value;
  const registerBtn = document.getElementById("registerBtn");
  if (password !== confirmPassword) {
    alert("Passwords do not match!");
    return;
  }
  registerBtn.classList.add("loading");
  registerBtn.disabled = true;
  fetch('logging/register.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `registerName=${encodeURIComponent(name)}&registerEmail=${encodeURIComponent(email)}&registerPassword=${encodeURIComponent(password)}&confirmPassword=${encodeURIComponent(confirmPassword)}`
  })
    .then(response => response.text())
    .then(() => {
      window.location.reload();
    })
    .catch(() => {
      alert("Registration failed. Please try again.");
    })
    .finally(() => {
      registerBtn.classList.remove("loading");
      registerBtn.disabled = false;
    });
}

// Utility function for notifications
function showNotification(message, type = "success") {
  const notification = document.createElement("div");
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === "success" ? "linear-gradient(135deg, #10B981, #059669)" : "linear-gradient(135deg, #EF4444, #DC2626)"};
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        font-weight: 600;
        z-index: 9999;
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        animation: slideIn 0.3s ease;
    `;
  notification.innerHTML = `<i class="fas fa-check-circle" style="margin-right: 8px;"></i>${message}`;
  document.body.appendChild(notification);
  setTimeout(() => {
    notification.remove();
  }, 3000);
}

// Filter drinks by type (hot/cold)
function filterDrinks(type) {
  lastDrinkType = type;
  document.getElementById("hotDrinksBtn").classList.remove("active");
  document.getElementById("coldDrinksBtn").classList.remove("active");
  if (type === "hot") {
    document.getElementById("hotDrinksBtn").classList.add("active");
  } else {
    document.getElementById("coldDrinksBtn").classList.add("active");
  }

  // Fade out all items first
  document.querySelectorAll('.product-item').forEach(item => {
    item.style.opacity = '0';
  });

  setTimeout(() => {
    document.querySelectorAll('.product-item').forEach(item => {
      if (item.getAttribute('data-type') === type) {
        item.style.display = '';
        setTimeout(() => { item.style.opacity = '1'; }, 10);
      } else {
        item.style.display = 'none';
      }
    });
  }, 200);
}

// Product view handler for "View" buttons
function handleViewProduct(id, name, price, description, image) {
  openProductModal(id, name, price, description, image);
}

// Event listeners
document.addEventListener("click", (event) => {
  const cartModal = document.getElementById("cartModal");
  const loginModal = document.getElementById("loginModal");
  const registerModal = document.getElementById("registerModal");
  const productModal = document.getElementById("productModal");
  if (event.target === cartModal) closeCart();
  if (event.target === loginModal || event.target === registerModal) closeAuthModal();
  if (event.target === productModal) closeProductModal();
});

// Initialize
document.addEventListener("DOMContentLoaded", () => {
  // Check PHP session login status
  if (typeof window.PHP_IS_LOGGED_IN !== "undefined" && window.PHP_IS_LOGGED_IN) {
    isLoggedIn = true;
    // Optionally set currentUser from PHP if available
    if (window.PHP_USER_FULLNAME) {
      currentUser = {
        name: window.PHP_USER_FULLNAME,
        initials: window.PHP_USER_FULLNAME.split(" ").map(n => n[0]).join("").toUpperCase().substring(0, 2)
      };
      document.getElementById("profileText").textContent = window.PHP_USER_FULLNAME.split(" ")[0];
      document.getElementById("profileAvatar").innerHTML = currentUser.initials;
    }
    // If PHP provides user_id, set it as well
    if (window.PHP_USER_ID) {
      currentUser.id = window.PHP_USER_ID;
    }
  } else {
    console.error("PHP_IS_LOGGED_IN is false or undefined.");
  }
  loadCart();
  updateCartCount();
  updateCartDisplay();
  showSection("home");
});

// Add CSS animation for notifications
const style = document.createElement("style");
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

// Fixed scroll event listener
window.addEventListener("scroll", () => {
  const header = document.querySelector(".header");
  const headerContent = document.querySelector(".header-content");
  const profileBtn = document.querySelector(".profile-btn");
  if (window.scrollY > 50) {
    header.classList.add("shrink");
    headerContent.classList.add("shrink");
    profileBtn.classList.add("shrink");
  } else {
    header.classList.remove("shrink");
    headerContent.classList.remove("shrink");
    profileBtn.classList.remove("shrink");
  }
});
window.addEventListener("scroll", () => {
  const header = document.querySelector(".header");
  const headerContent = document.querySelector(".header-content");
  if (window.scrollY > 50) {
    header.classList.add("shrink");
    headerContent.classList.add("shrink");
  } else {
    header.classList.remove("shrink");
    headerContent.classList.remove("shrink");
  }
});


function handleViewProduct(id, name, price, description, image) {
  if (!isLoggedIn) {
    showLoginModal();
    return;
  }
  currentProduct = { id, name, price, description, image };

  // Set price per product and size
  let grandePrice = price;
  let supremePrice = price;
  // Premium Coffee
  if (
    id.startsWith("ameri") ||
    id.startsWith("caramel-macchiato") ||
    id.startsWith("spanish-latte") ||
    id.startsWith("vanilla-latte") ||
    id.startsWith("mocha") ||
    id.startsWith("white") ||
    id.startsWith("salted-caramel")
  ) {
    grandePrice = 120;
    supremePrice = 150;
  }
  // Specialty Coffee
  else if (
    id.startsWith("ube") ||
    id.startsWith("honey") ||
    id.startsWith("dolce") ||
    id.startsWith("cacao") ||
    id.startsWith("cafe-con-leche") ||
    id.startsWith("sea-salt-mocha") ||
    id.startsWith("creamy-pistachio-latte") ||
    id.startsWith("peppermint-mocha")
  ) {
    grandePrice = 150;
    supremePrice = 180;
  }
  // Chocolate Overload
  else if (
    id.startsWith("toblerone-kick") ||
    id.startsWith("kisses") ||
    id.startsWith("oreo") ||
    id.startsWith("kitkat-break") ||
    id.startsWith("mms-burst")
  ) {
    grandePrice = 150;
    supremePrice = 180;
  }
  // Matcha Series
  else if (
    id.startsWith("matcha-latte") ||
    id.startsWith("white-choco-matcha") ||
    id.startsWith("berry-matcha") ||
    id.startsWith("dirty-matcha")
  ) {
    grandePrice = 160;
    supremePrice = 190;
  }
  // Milk Based
  else if (
    id.startsWith("strawberry-cloud") ||
    id.startsWith("minty-choco") ||
    id.startsWith("white-chocolate")
  ) {
    grandePrice = 99;
    supremePrice = 120;
  }
  // All Time Fave
  else if (
    id.startsWith("milo-dinosaur") ||
    id.startsWith("ube-cloud")
  ) {
    grandePrice = 99;
    supremePrice = 120;
  }
  // Default fallback
  else {
    grandePrice = price;
    supremePrice = price;
  }

  // Set currentProduct.price based on selectedSize
  currentProduct.price = selectedSize === "Grande" ? grandePrice : supremePrice;

  // Show only the price for the selected size
  let priceText = "";
  if (selectedSize === "Grande") {
    priceText = `Php ${grandePrice} (Grande)`;
  } else {
    priceText = `Php ${supremePrice} (Supreme)`;
  }
  document.getElementById("modalProductName").textContent = name
  document.getElementById("modalProductPrice").textContent = priceText
  document.getElementById("modalProductDescription").textContent = description
  document.getElementById("modalProductImage").src = image
  document.getElementById("modalProductImage").alt = name

  // Reset size selection and set click events for both buttons
  document.querySelectorAll(".size-btn").forEach((btn) => {
    btn.classList.remove("active");
    if (btn.textContent.trim() === selectedSize) btn.classList.add("active");
    btn.onclick = function () {
      selectSize(btn.textContent.trim(), grandePrice, supremePrice, name);
    };
  });

  // Show modal: center it using flex and ensure it's visible
  const modal = document.getElementById("productModal");
  modal.style.display = "flex";
  modal.style.alignItems = "center";
  modal.style.justifyContent = "center";
  modal.style.position = "fixed";
  modal.style.top = "0";
  modal.style.left = "0";
  modal.style.width = "100vw";
  modal.style.height = "100vh";
  modal.style.background = "rgba(0,0,0,0.15)";
  modal.style.zIndex = "3000";
  modal.classList.add("active");
  document.body.style.overflow = "hidden";



  // Scroll to top to ensure modal is visible (fix for mobile/small screens)
  window.scrollTo({ top: 0, behavior: "auto" });

  // Remove pickup form if present
  let pickupForm = document.getElementById("pickupFormModal");
  if (pickupForm) pickupForm.remove();

  // Move the "Add to Cart" button to the bottom of the modal (if not already)
  const detailsSection = document.querySelector(".product-modal-details");
  const addBtn = detailsSection.querySelector(".product-modal-add-cart");
  if (addBtn) {
    // Remove any previous click handler to avoid stacking
    const newBtn = addBtn.cloneNode(true);
    addBtn.parentNode.replaceChild(newBtn, addBtn);
    // Move to the end of detailsSection
    detailsSection.appendChild(newBtn);
    newBtn.onclick = function () {
      addProductToCart();
      // Close the modal after adding to cart
      modal.classList.remove("active");
      modal.style.display = "none";
      document.body.style.overflow = "auto";
    };
  }
}

// Update price when size is changed
function selectSize(size, grandePrice, supremePrice, name) {
  selectedSize = size;
  document.querySelectorAll(".size-btn").forEach((btn) => {
    btn.classList.remove("active");
    if (btn.textContent.trim() === size) btn.classList.add("active");
  });
  // Update price and modal content for the selected size, keep name
  if (currentProduct) {
    let price = size === "Grande" ? (grandePrice || currentProduct.price) : (supremePrice || currentProduct.price);
    currentProduct.price = price;
    let priceText = size === "Grande"
      ? `Php ${grandePrice || currentProduct.price} (Grande)`
      : `Php ${supremePrice || currentProduct.price} (Supreme)`;
    document.getElementById("modalProductPrice").textContent = priceText;
    document.getElementById("modalProductName").textContent = name || currentProduct.name;
  }
}


// Utility: Check for JS errors and modal visibility
function debugSignInModal() {
  // 1. Check if the modal exists
  const modal = document.getElementById("loginModal");
  if (!modal) {
    alert("Login modal element not found. Check your HTML.");
    return;
  }
  // 2. Check if the modal is visible
  if (!modal.classList.contains("active")) {
    alert("Login modal is not active. The showAuthModal() function may not be called.");
    return;
  }
  // 3. Check for overlay issues
  const rect = modal.getBoundingClientRect();
  const elementsAtPoint = document.elementsFromPoint(rect.left + 10, rect.top + 10);
  if (elementsAtPoint.length > 0 && elementsAtPoint[0] !== modal && !modal.contains(elementsAtPoint[0])) {
    alert("Another element may be covering the modal. Check your z-index and overlay CSS.");
    return;
  }
  // 4. Check for JS errors
  if (window.console && window.console.log) {
    alert("If you still can't sign in, open the browser console (F12) and check for errors.");
  }
  alert("Login modal is present and active. If you still can't sign in, check for JavaScript errors or event handler issues.");
}
// Handle checkout button click
function handleCheckout() {
  const deliveryOptions = document.getElementById("deliveryOptions");

  // If the pickup form is not visible, show it and return (first click)
  if (!deliveryOptions || deliveryOptions.style.display !== "block") {
    startCheckout();
    return;
  }

  // If the pickup form is visible, validate and submit (second click)
  const pickup_name = document.getElementById("pickupName") ? document.getElementById("pickupName").value : "";
  const pickup_location = document.getElementById("pickupLocation") ? document.getElementById("pickupLocation").value : "";
  const pickup_time = document.getElementById("pickupTime") ? document.getElementById("pickupTime").value : "";
  const special_instructions = document.getElementById("specialInstructions") ? document.getElementById("specialInstructions").value : "";

  // Validate pickup time is between 15:00 and 21:00
  if (pickup_time) {
    const [h, m] = pickup_time.split(":").map(Number);
    const mins = h * 60 + m;
    if (mins < 15 * 60 || mins > 21 * 60) {
      showNotification("Pickup time must be between 3:00 p.m and 9:00 p.m.", "error");
      return;
    }
  }

  if (!pickup_name || !pickup_location || !pickup_time) {
    showNotification("Please fill out all required pickup details.", "error");
    return;
  }

  // Send cart items as JSON string, using product_id
  fetch('validations/pickup_checkout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body:
      `pickup_name=${encodeURIComponent(pickup_name)}` +
      `&pickup_location=${encodeURIComponent(pickup_location)}` +
      `&pickup_time=${encodeURIComponent(pickup_time)}` +
      `&special_instructions=${encodeURIComponent(special_instructions)}` +
      `&cart_items=${encodeURIComponent(JSON.stringify(cart))}`
  })
  .then(res => res.text())
  .then(text => {
    console.log('Pickup checkout raw response:', text);
    if (!text.trim()) {
      showNotification("Empty server response.", "error");
      return;
    }

    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      showNotification("Invalid server response: " + text, "error");
      return;
    }

    if (data.success) {
      showNotification("Pickup order placed successfully!", "success");
      closeCart();
      cart = [];
      updateCartCount();
      updateCartDisplay();

      // Show receipt modal with reference number if available
      if (typeof showReceiptModal === "function" && data.reference_number) {
        showReceiptModal(data.reference_number);
      }
    } else {
      showNotification(data.message || "Pickup order failed.", "error");
    }
  })
  .catch((err) => {
    console.error('Pickup checkout error:', err);
    showNotification("Network error. Please try again.", "error");
  });
}

function handlePickupCheckout() {
  const pickup_name = document.getElementById("pickupName").value;
  const pickup_location = document.getElementById("pickupLocation").value;
  const pickup_time = document.getElementById("pickupTime").value;
  const special_instructions = document.getElementById("specialInstructions").value;

  fetch('validations/pickup_checkout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `pickup_name=${encodeURIComponent(pickup_name)}&pickup_location=${encodeURIComponent(pickup_location)}&pickup_time=${encodeURIComponent(pickup_time)}&special_instructions=${encodeURIComponent(special_instructions)}`
  })
  .then(res => {
    console.log('Pickup checkout response status:', res.status);
    return res.text().then(text => {
      console.log('Pickup checkout raw response:', text);
      try {
        return JSON.parse(text);
      } catch (e) {
        showNotification("Invalid server response.", "error");
        throw e;
      }
    });
  })
  .then(data => {
    if (data.success) {
      showNotification("Pickup order placed successfully!", "success");
      closeCart();
      cart = [];
      updateCartCount();
      updateCartDisplay();
    } else {
      showNotification(data.message || "Pickup order failed.", "error");
    }
  })
  .catch((err) => {
    console.error('Pickup checkout error:', err);
    showNotification("Network error. Please try again.", "error");
  });
}



