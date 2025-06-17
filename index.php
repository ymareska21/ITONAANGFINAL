<?php
session_start();
$isLoggedIn = isset($_SESSION['user']);
$userFullName = $isLoggedIn ? $_SESSION['user']['user_FN'] : '';

// Fetch product statuses for filtering
$productStatuses = [];
$conn = new mysqli('localhost', 'root', '', 'ordering');
$allProducts = [];
if (!$conn->connect_error) {
    // Fetch all products for dynamic rendering
    $res = $conn->query("SELECT * FROM products");
    while ($row = $res->fetch_assoc()) {
        $allProducts[] = $row;
        $productStatuses[$row['id']] = $row['status'];
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cups & Cuddles   </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">C&C</div>
            <button class="hamburger-menu">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="nav-menu">
                <a href="#" class="nav-item active" onclick="showSection('home')">Home</a>
                <a href="#" class="nav-item" onclick="showSection('about')">About </a>
                <a href="#" class="nav-item" onclick="showSection('products')">Shop</a>
                <a href="#" class="nav-item" onclick="showSection('locations')">Locations</a>
        
                
                <div class="profile-dropdown">
                    <button class="profile-btn" id="profileDropdownBtn" onclick="toggleProfileDropdown(event)">
                        <div class="profile-avatar" id="profileAvatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span id="profileText">
                            <?php echo $isLoggedIn ? htmlspecialchars(explode(' ', $userFullName)[0]) : 'Sign In'; ?>
                        </span>
                        <i class="fas fa-caret-down ms-1"></i>
                    </button>
                    <div class="profile-dropdown-menu" id="profileDropdownMenu">
                        <?php if ($isLoggedIn): ?>
                            <a href="validations/order_history.php" class="dropdown-item">Order History</a>
                            <a href="#" class="dropdown-item" onclick="logout(); return false;">Logout</a>
                        <?php else: ?>
                            <a href="#" class="dropdown-item" onclick="showLoginModal(); return false;">Sign In</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isLoggedIn): ?>
                    <span class="navbar-username" style="margin-left:10px;font-weight:600;">
                        <?php echo htmlspecialchars($userFullName); ?>
                    </span>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Login Modal -->
    <div id="loginModal" class="auth-modal">
    <div class="auth-content">
        <button class="close-auth" onclick="closeAuthModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="auth-header">
            <h3>Welcome Back!</h3>
            <p>Sign in to your Cups & Cuddles account</p>
        </div>
        <div id="loginSuccess" class="success-message">
            <i class="fas fa-check-circle"></i>
            Welcome back! You're now signed in.
        </div>
        <form class="auth-form" onsubmit="handleLogin(event); return false;">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="loginFullname" placeholder="Enter your full name" required>
                <div id="loginFullnameError" class="text-danger small"></div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="loginPassword" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="auth-btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>
        <div class="auth-switch">
            <p>New to Cups & Cuddles? <a onclick="switchToRegister()">Create an account</a></p>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div id="registerModal" class="auth-modal">
    <div class="auth-content">
        <button class="close-auth" onclick="closeAuthModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="auth-header">
            <h3>Join Us!</h3>
            <p>Create your account and start your coffee journey</p>
        </div>
        <div id="registerSuccess" class="success-message">
            <i class="fas fa-check-circle"></i>
            Account created! Welcome to Cups & Cuddles.
        </div>
        <form class="auth-form" id="registerForm" onsubmit="handleRegister(event); return false;">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="registerName" id="registerName" placeholder="Enter your full name" required>
                <div id="fullnameError" class="text-danger small"></div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="registerEmail" id="registerEmail" placeholder="Enter your email" required>
                <div id="emailError" class="text-danger small"></div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="registerPassword" id="registerPassword" placeholder="Create a secure password" required>
                <div id="passwordError" class="text-danger small"></div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Confirm your password" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 8px; display: flex; align-items: flex-start; justify-content: flex-start;">
  <label for="acceptTerms" style="font-size: 0.97em; display: flex; align-items: center; gap: 3px; margin-bottom: 0;">
    <input type="checkbox" id="acceptTerms" required >
    I accept the
    <button type="button" id="showTermsBtn" style="background: none; border: none; color: #40534b; text-decoration: underline; cursor: pointer; padding: 0; font-size: 1em; margin: 0;">
      Terms and Conditions
    </button>
  </label>
</div>
            <button type="submit" class="auth-btn" id="registerBtn">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>
        </form>
        <div class="auth-switch">
            <p>Already have an account? <a onclick="switchToLogin()">Sign in here</a></p>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div id="termsModal" class="auth-modal" style="z-index:4000;">
    <div class="auth-content" style="max-width:600px;">
        <button class="close-auth" onclick="document.getElementById('termsModal').classList.remove('active')">
            <i class="fas fa-times"></i>
        </button>
        <div class="auth-header">
            <h3>Terms and Conditions</h3>
        </div>
        <div style="max-height:50vh;overflow-y:auto;text-align:left;font-size:1em;color:#374151;padding-bottom:12px;">
            <p>
                Welcome to Cups & Cuddles! By creating an account, you agree to the following terms:
            </p>
            <ul style="padding-left:18px;">
                <li>Your information will be used for order processing and account management.</li>
                <li>We will not share your personal data with third parties except as required by law.</li>
                <li>You are responsible for keeping your account credentials secure.</li>
                <li>All purchases are subject to our shop policies and operating hours.</li>
                <li>We reserve the right to update these terms at any time.</li>
            </ul>
            <p>
                For questions, please contact us at <a href="mailto:cupsandcuddles@gmail.com">cupsandcuddles@gmail.com</a>.
            </p>
        </div>
        <div style="text-align:center;">
            <button class="auth-btn" type="button" onclick="document.getElementById('termsModal').classList.remove('active')">Close</button>
        </div>
    </div>
</div>

    <!-- Home Section -->
    <div id="home" class="section-content home-section">
        <!-- Hero Section -->
        <section class="hero-section">
            <!-- Floating Coffee Beans -->
            <div class="coffee-bean"></div>
            <div class="coffee-bean"></div>
            <div class="coffee-bean"></div>
            <div class="coffee-bean"></div>
            <div class="coffee-bean"></div>
            <div class="coffee-bean"></div>

            <div class="hero-content">
                <h1>CUPS</h1><h3>&</h3>
                
            </div>
            <div class="hero-content2">
                <h2>CUDDLES</h2>
            </div>

            <!-- Coffee Image Overlay -->
            <div class="coffee-image">
                <img src="img/cupss.png" alt="Iced Coffee">
            </div>
          
        </section>

        <!-- Bottom Cards Section -->
        <section class="cards-section">
            <div class="cards-grid">
                <div class="card card-orange">
                    <img src="img/pic1.jpg" alt="Delicious Pastry">
                </div>
                <div class="card card-green">
                    <img src="img/blend.jpg" alt="Delicious Pastry">
                </div>
            </div>
        </section>

        <section class="cards-section">
            <div class="cards-grid2">
                <div class="card card-orange2 position-relative overflow-hidden">
                    <!-- Background image -->
                    <img src="img/first.jpg" alt="Delicious Pastry" class="img-fluid w-100 h-auto">
            
                    <!-- Spinning circular text -->
                    <div class="circle-wrapper position-absolute top-50 start-50 translate-middle">
                        <!-- White circle background -->
                        <div class="circle-bg"></div>
            
                        <!-- Center icon -->
                        <div class="center-icon">♥</div>
            
                        <!-- SVG spinning text -->
                        <svg viewBox="0 0 200 200" class="rotating-text">
                            <defs>
                                <path
                                    id="circlePath"
                                    d="M 100, 100 m -75, 0 a 75,75 0 1,1 150,0 a 75,75 0 1,1 -150,0"
                                />
                            </defs>
                            <text>
                                <textPath href="#circlePath" startOffset="0%">
                                    • GO - TO • MOBILE • CAFE • IN CALABARZON 
                                </textPath>
                            </text>
                        </svg>
                    </div>
                </div>
                
                <div class="card card-green2">
                    <img src="img/pic2.jpg" alt="Delicious Pastry">
                </div>
            </div>
        </section>

        <!-- Impact Stories Section -->
        <section class="impact-stories">
          <div class="section-header">
            <h2>Start Your Own Coffee Business: the Cups and Cuddles way! ☕︎</h2>
            <p>Turn your love for coffee into a thriving business today! Message our socials to know more and get started! 📨</p>
          </div>

          
            <!-- Gradient Overlays -->
            <div class="fade-left"></div>
            <div class="fade-right"></div>

            <div class="carousel-track">
              <!-- Only display testimonial images, no text or names -->
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/promo1.jpg" alt="Testimonial 1">
                </div>
              </div>
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/promo2.jpg" alt="Testimonial 2">
                </div>
              </div>
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/promo3.jpg" alt="Testimonial 3">
                </div>
              </div>
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/promo4.jpg" alt="Testimonial 4">
                </div>
              </div>
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/book1.jpg" alt="Testimonial 5">
                </div>
              </div>
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/book2.jpg" alt="Testimonial 6">
                </div>
              </div>
               <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/book3.jpg" alt="Testimonial 7">
                </div>
              </div>
              <!-- Duplicated for infinite loop -->
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/promo1.jpg" alt="Testimonial 1">
                </div>
              </div>
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/promo2.jpg" alt="Testimonial 2">
                </div>
              </div>
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/promo3.jpg" alt="Testimonial 3">
                </div>
              </div>
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/promo4.jpg" alt="Testimonial 4">
                </div>
              </div>
               
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/book1.jpg" alt="Testimonial 5">
                </div>
              </div>
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/book2.jpg" alt="Testimonial 6">
                </div>
              </div>
              <div class="testimonial">
                <div class="testimonial-header">
                  <img src="img/book3.jpg" alt="Testimonial 7">
                </div>
              </div>
            </div>
          </div>
        </section>

  
    
    <!-- About Section - COMPLETELY REDESIGNED -->
    <div id="about" class="section-content about-section">
        <!-- Hero Header -->
        <section class="about-hero-header position-relative overflow-hidden">
            <div class="about-hero-overlay"></div>
            <div class="container-fluid h-100">
                <div class="row h-100 align-items-center justify-content-center text-center text-white">
                    <div class="col-12">
                        <h1 class="about-hero-title">ABOUT US</h1>
                        <p class="about-hero-subtitle">The go-to mobile cafe around Calabarzon ✨🤍 
                            Premium artisan beverages. Great Chat. Friendly Baristas.</p>
                    </div>
                </div>
            </div>
            
            <!-- Floating coffee beans -->
            <div class="about-floating-bean about-bean-1"></div>
            <div class="about-floating-bean about-bean-2"></div>
            <div class="about-floating-bean about-bean-3"></div>
        </section>

        <div class="container-fluid px-4 py-5">
            <!-- Our Story Section -->
            <section class="about-story-section py-5">
                <div class="container">
                    <div class="row align-items-center g-5">
                        <div class="col-lg-6">
                            <div class="about-image-container position-relative">
                                <div class="about-image-bg"></div>
                                <img src="img/pic1.jpg" alt="Coffee shop interior" class="about-main-image img-fluid rounded-4 shadow-lg">
                                <div class="about-floating-badge">
                                    <div class="d-flex align-items-center">
                                        <div class="about-badge-icon">
                                            <i class="fas fa-coffee"></i>
                                        </div>
                                        <div class="ms-3">
                                            <div class="about-badge-title">Est. 2024</div>
                                            <div class="about-badge-subtitle">Serving Excellence</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="about-story-content">
                                <span class="about-section-badge">Our Story</span>
                                <h2 class="about-section-title mb-4">Let's connect over coffee?</h2>
                                <p class="about-story-text mb-4">
                                    At Cups and Cuddles, we’re more than just a mobile coffee shop — we’re a cozy experience on wheels.
                                    Founded with a passion for great coffee and warm connections, our mission is to bring handcrafted beverages and a welcoming atmosphere wherever we go. 
                                </p>
                                <p class="about-story-text mb-4">
                                    Whether you’re starting your day or taking a much-needed break, our mobile café is your go-to spot for comforting cups and friendly vibes. 
                                    Every brew is made with care, and every visit is a chance to slow down, sip, and smile.
                                </p>
                                <div class="d-flex align-items-center pt-3">
                                    <div class="about-avatar-group">
                                        <div class="about-avatar about-avatar-1"></div>
                                        <div class="about-avatar about-avatar-2"></div>
                                        <div class="about-avatar about-avatar-3"></div>
                                    </div>
                                    <small class="ms-3 text-muted fw-medium">Trusted by coffee lovers across Lipa — bringing warmth, one cup at a time.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Values Section -->
            <section class="about-values-section py-5">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="about-section-badge about-amber">More About Us</span>
                        <h2 class="about-section-title mb-4">Why Cups and Cuddles?</h2>
                        <p class="about-section-subtitle mx-auto">
                            Every decision we make is guided by our core values that shape who we are and how we serve our community.
                        </p>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6 col-lg-4">
                            <div class="about-value-card">
                                <div class="about-value-icon about-red">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <h3 class="about-value-title">Passion</h3>
                                <p class="about-value-description">We pour our heart into every cup, ensuring each sip brings warmth, joy, and a moment of comfort.</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-4">
                            <div class="about-value-card">
                                <div class="about-value-icon about-blue">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="about-value-title">Community</h3>
                                <p class="about-value-description">We’re all about building connections — turning simple coffee moments into lasting relationships.</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-4">
                            <div class="about-value-card">
                                <div class="about-value-icon about-emerald">
                                    <i class="fas fa-award"></i>
                                </div>
                                <h3 class="about-value-title">Quality</h3>
                                <p class="about-value-description">From bean to cup, we uphold the highest standards to deliver consistently excellent coffee experiences.</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-4">
                            <div class="about-value-card">
                                <div class="about-value-icon about-green">
                                    <i class="fas fa-leaf"></i>
                                </div>
                                <h3 class="about-value-title">Sustainability</h3>
                                <p class="about-value-description">We believe great coffee shouldn't come at the planet’s expense — our practices support a greener future.</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-4">
                            <div class="about-value-card">
                                <div class="about-value-icon about-amber-icon">
                                    <i class="fas fa-coffee"></i>
                                </div>
                                <h3 class="about-value-title">Craftsmanship</h3>
                                <p class="about-value-description">Every drink is artfully crafted with skill, care, and creativity to elevate your daily coffee ritual.</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-4">
                            <div class="about-value-card">
                                <div class="about-value-icon about-purple">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 class="about-value-title">Consistency</h3>
                                <p class="about-value-description">You can count on us — same great taste, same cozy vibes, no matter where you find us.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Team Section -->
            <section class="about-team-section py-5">
    <div class="container">
        <div class="about-team-container">
            <div class="text-center mb-5">
                <span class="about-section-badge about-emerald-badge">Meet Our Team</span>
                <h2 class="about-section-title mb-4">The People Behind the Magic</h2>
                <p class="about-section-subtitle mx-auto">
                    Our passionate team of coffee enthusiasts and hospitality experts work together to create exceptional experiences.
                </p>
            </div>
            <div class="row g-5 justify-content-center">
                <div class="col-md-4 d-flex justify-content-center">
                    <div class="about-team-member team-left text-center position-relative">
                        <span class="team-bubble" aria-hidden="true"></span>
                        <div class="about-member-image-container">
                            <div class="about-member-image-bg"></div>
                            <img src="img/owner.jpg" alt="Hazel Anne Haylo" class="about-member-image">
                        </div>
                        <h3 class="about-member-name">Hazel Anne Haylo</h3>
                    </div>
                </div>
                <div class="col-md-4 d-flex justify-content-center">
                    <div class="about-team-member team-right text-center position-relative">
                        <span class="team-bubble" aria-hidden="true"></span>
                        <div class="about-member-image-container">
                            <div class="about-member-image-bg"></div>
                            <img src="img/owner1.jpg" alt="Jeben Rowe Villaluz" class="about-member-image">
                        </div>
                        <h3 class="about-member-name">Jeben Rowe Villaluz</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

            <!-- Visit Us CTA -->
            <section class="about-cta-section py-5">
                <div class="container">
                    <div class="about-cta-container text-center text-white position-relative overflow-hidden">
                        <div class="about-cta-overlay"></div>
                        <div class="position-relative">
                            <h2 class="about-cta-title mb-4">Ready to Experience Cups and Cuddles?</h2>
                            <p class="about-cta-subtitle mb-5">
                                Visit us today and discover why we're more than just a coffee shop – Start your day with Cups and Cuddles.
                            </p>
                            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                                <button class="btn btn-light btn-lg about-cta-btn-primary" onclick="showSection('locations')">Find Our Locations</button>
                                <button class="btn btn-outline-light btn-lg about-cta-btn-secondary" onclick="showSection('products')">View Our Menu</button>
                            </div>
                        </div>
                        
                        <!-- Decorative elements -->
                        <div class="about-cta-decoration about-decoration-1"></div>
                        <div class="about-cta-decoration about-decoration-2"></div>
                        <div class="about-cta-decoration about-decoration-3"></div>
                        <div class="about-cta-decoration about-decoration-4"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Products Section -->
    <div id="products" class="section-content products-section">
    <section class="products-hero-header position-relative overflow-hidden">
            <div class="products-hero-overlay"></div>
            <div class="container-fluid h-100">
                <div class="row h-100 align-items-center justify-content-center text-center text-white">
                    <div class="col-12">
                        <h1 class="products-hero-title">Shop Now</h1>
                        <p class="products-hero-subtitle">Crafting moments, one cup at a time</p>
                    </div>
                </div>
            </div>
            
            <!-- Floating coffee beans -->
            <div class="products-floating-bean products-bean-1"></div>
            <div class="products-floating-bean products-bean-2"></div>
            <div class="products-floating-bean products-bean-3"></div>
        </section>


    <!-- Hot/Cold Drinks Toggle Buttons -->
    <div class="d-flex justify-content-center my-4">
        <button class="btn btn-outline-dark mx-2" id="hotDrinksBtn" onclick="filterDrinks('hot')">Hot Drinks</button>
        <button class="btn btn-outline-dark mx-2" id="coldDrinksBtn" onclick="filterDrinks('cold')">Cold Drinks</button>
    </div>

    <div class="products-header">
        <div class="delivery-badge">
            <i class="fas fa-truck"></i>
        </div>
        <h2>Roasted goodness to your doorstep!</h2>
    </div>

    <!-- Premium Coffee Section (Dynamic + Hardcoded) -->
    <div class="products-header">
    <h3 style="font-size:2rem;font-weight:700;margin-bottom:0.5em;">Premium Coffee</h3>
    <div style="font-size:1.1rem;font-weight:500;margin-bottom:1.5em;">
        <span>Grande - Php 120</span> &nbsp;|&nbsp; <span>Supreme - Php 150</span>
    </div>
</div>
<div class="product-list">
    <?php
    $shownIds = [];
    $premiumIndex = 0;
    foreach ($allProducts as $product) {
        if (
            strtolower($product['category']) === 'premium coffee'
            && $product['status'] === 'active'
        ) {
            $shownIds[] = $product['id'];
            $imgSrc = $product['image'];
            if (strpos($imgSrc, 'img/') !== 0) {
                $imgSrc = 'img/' . ltrim($imgSrc, '/');
            }
            // Determine if this is a hot or cold product based on the id or name
            $dataType = (stripos($product['id'], 'hot') !== false || stripos($product['name'], 'hot') !== false) ? 'hot' : 'cold';
            ?>
            <div class="product-item card-premium-<?= $premiumIndex ?>" data-type="<?= $dataType ?>">
                <div class="product-image">
                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <span class="badge bg-success mb-2">Premium Coffee</span>
                    <p><?= htmlspecialchars($product['description']) ?></p>
                    <div class="product-footer">
                        <button class="view-btn" onclick="handleViewProduct('<?= $product['id'] ?>', '<?= htmlspecialchars($product['name']) ?>', 120, '<?= htmlspecialchars($product['description']) ?>', '<?= htmlspecialchars($imgSrc) ?>')">View</button>
                    </div>
                </div>
            </div>
            <?php
            $premiumIndex++;
        }
    }
    // 2. Show hardcoded products if they are not already shown from the database
    $premiumProducts = [
        [
            'id' => 'ameri',
            'name' => 'Americano',
            'cold_img' => 'img/ameri.jpg',
            'hot_img' => 'img/HOT MARI.jpg',
            'cold_desc' => 'A bold and simple espresso diluted with hot water for a smooth, black coffee.',
            'hot_desc' => 'A strong espresso-based drink diluted with hot water; bold and smooth.',
        ],
        [
            'id' => 'caramel-macchiato',
            'name' => 'Caramel Macchiato',
            'cold_img' => 'img/caramel.jpg',
            'hot_img' => 'img/HOT MARI.jpg',
            'cold_desc' => 'A layered espresso drink with milk and rich caramel drizzle.',
            'hot_desc' => 'Steamed milk with espresso and a swirl of rich caramel sauce.',
        ],
        // ...existing code for other hardcoded products...
    ];
    foreach ($premiumProducts as $p) {
        if (!in_array($p['id'], $shownIds) && (!isset($productStatuses[$p['id']]) || $productStatuses[$p['id']] === 'active')) {
            // Cold
            ?>
            <div class="product-item" data-type="cold">
                <div class="product-image">
                    <img src="<?= htmlspecialchars($p['cold_img']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <span class="badge bg-success mb-2">Premium Coffee</span>
                    <p><?= htmlspecialchars($p['cold_desc']) ?></p>
                    <div class="product-footer">
                        <button class="view-btn" onclick="handleViewProduct('<?= $p['id'] ?>', '<?= htmlspecialchars($p['name']) ?>', 120, '<?= htmlspecialchars($p['cold_desc']) ?>', '<?= htmlspecialchars($p['cold_img']) ?>')">View</button>
                    </div>
                </div>
            </div>
            <?php
            // Hot
            ?>
            <div class="product-item" data-type="hot">
                <div class="product-image">
                    <img src="<?= htmlspecialchars($p['hot_img']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <span class="badge bg-success mb-2">Premium Coffee</span>
                    <p><?= htmlspecialchars($p['hot_desc']) ?></p>
                    <div class="product-footer">
                        <button class="view-btn" onclick="handleViewProduct('<?= $p['id'] ?>', '<?= htmlspecialchars($p['name']) ?>', 120, '<?= htmlspecialchars($p['hot_desc']) ?>', '<?= htmlspecialchars($p['hot_img']) ?>')">View</button>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    ?>
</div>

    <!-- Specialty Coffee Section -->
    <div class="products-header" style="margin-top:2em;">
        <h3 style="font-size:2rem;font-weight:700;margin-bottom:0.5em;">Specialty Coffee</h3>
        <div style="font-size:1.1rem;font-weight:500;margin-bottom:1.5em;">
            <span>Grande - Php 150</span> &nbsp;|&nbsp; <span>Supreme - Php 180</span>
        </div>
    </div>
    <div class="product-list">
        <?php
        $shownIds = [];
        foreach ($allProducts as $product) {
            if (
                strtolower($product['category']) === 'specialty coffee'
                && $product['status'] === 'active'
            ) {
                $shownIds[] = $product['id'];
                $imgSrc = $product['image'];
                if (strpos($imgSrc, 'img/') !== 0) {
                    $imgSrc = 'img/' . ltrim($imgSrc, '/');
                }
                // Determine if this is a hot or cold product based on the id or name
                $dataType = (stripos($product['id'], 'hot') !== false || stripos($product['name'], 'hot') !== false) ? 'hot' : 'cold';
                ?>
                <div class="product-item" data-type="<?= $dataType ?>">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <span class="badge bg-warning mb-2">Specialty Coffee</span>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <div class="product-footer">
                            <button class="view-btn" onclick="handleViewProduct('<?= $product['id'] ?>', '<?= htmlspecialchars($product['name']) ?>', 150, '<?= htmlspecialchars($product['description']) ?>', '<?= htmlspecialchars($imgSrc) ?>')">View</button>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <!-- Chocolate Overload Section -->
    <div class="products-header">
        <h3 style="font-size:2rem;font-weight:700;margin-bottom:0.5em;">Chocolate Overload</h3>
        <div style="font-size:1.1rem;font-weight:500;margin-bottom:1.5em;">
            <span>Grande - Php 150</span> &nbsp;|&nbsp; <span>Supreme - Php 180</span>
        </div>
    </div>
    <div class="product-list">
        <?php
        $shownIds = [];
        foreach ($allProducts as $product) {
            if (
                strtolower($product['category']) === 'chocolate overload'
                && $product['status'] === 'active'
            ) {
                $shownIds[] = $product['id'];
                $imgSrc = $product['image'];
                if (strpos($imgSrc, 'img/') !== 0) {
                    $imgSrc = 'img/' . ltrim($imgSrc, '/');
                }
                // Determine if this is a hot or cold product based on the id or name
                $dataType = (stripos($product['id'], 'hot') !== false || stripos($product['name'], 'hot') !== false) ? 'hot' : 'cold';
                ?>
                <div class="product-item" data-type="<?= $dataType ?>">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <span class="badge bg-secondary mb-2">Chocolate Overload</span>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <div class="product-footer">
                            <button class="view-btn" onclick="handleViewProduct('<?= $product['id'] ?>', '<?= htmlspecialchars($product['name']) ?>', 150, '<?= htmlspecialchars($product['description']) ?>', '<?= htmlspecialchars($imgSrc) ?>')">View</button>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <!-- Matcha Series Section -->
    <div class="products-header" style="margin-top:2em;">
        <h3 style="font-size:2rem;font-weight:700;margin-bottom:0.5em;">Matcha Series</h3>
        <div style="font-size:1.1rem;font-weight:500;margin-bottom:1.5em;">
            <span>Grande - Php 160</span> &nbsp;|&nbsp; <span>Supreme - Php 190</span>
        </div>
    </div>
    <div class="product-list">
        <?php
        $shownIds = [];
        foreach ($allProducts as $product) {
            if (
                strtolower($product['category']) === 'matcha series'
                && $product['status'] === 'active'
            ) {
                $shownIds[] = $product['id'];
                $imgSrc = $product['image'];
                if (strpos($imgSrc, 'img/') !== 0) {
                    $imgSrc = 'img/' . ltrim($imgSrc, '/');
                }
                // Determine if this is a hot or cold product based on the id or name
                $dataType = (stripos($product['id'], 'hot') !== false || stripos($product['name'], 'hot') !== false) ? 'hot' : 'cold';
                ?>
                <div class="product-item" data-type="<?= $dataType ?>">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <span class="badge bg-success mb-2">Matcha Series</span>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <div class="product-footer">
                            <button class="view-btn" onclick="handleViewProduct('<?= $product['id'] ?>', '<?= htmlspecialchars($product['name']) ?>', 160, '<?= htmlspecialchars($product['description']) ?>', '<?= htmlspecialchars($imgSrc) ?>')">View</button>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <!-- Milk Based Section -->
    <div class="products-header" style="margin-top:2em;">
        <h3 style="font-size:2rem;font-weight:700;margin-bottom:0.5em;">Milk Based</h3>
        <div style="font-size:1.1rem;font-weight:500;margin-bottom:1.5em;">
            <span>Grande - Php 99</span> &nbsp;|&nbsp; <span>Supreme - Php 120</span>
        </div>
    </div>
    <div class="product-list">
        <?php
        $shownIds = [];
        foreach ($allProducts as $product) {
            if (
                strtolower($product['category']) === 'milk based'
                && $product['status'] === 'active'
            ) {
                $shownIds[] = $product['id'];
                $imgSrc = $product['image'];
                if (strpos($imgSrc, 'img/') !== 0) {
                    $imgSrc = 'img/' . ltrim($imgSrc, '/');
                }
                // Determine if this is a hot or cold product based on the id or name
                $dataType = (stripos($product['id'], 'hot') !== false || stripos($product['name'], 'hot') !== false) ? 'hot' : 'cold';
                ?>
                <div class="product-item" data-type="<?= $dataType ?>">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <span class="badge bg-info mb-2">Milk Based</span>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <div class="product-footer">
                            <button class="view-btn" onclick="handleViewProduct('<?= $product['id'] ?>', '<?= htmlspecialchars($product['name']) ?>', 99, '<?= htmlspecialchars($product['description']) ?>', '<?= htmlspecialchars($imgSrc) ?>')">View</button>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <!-- All Time Fave Section -->
    <div class="products-header" style="margin-top:2em;">
        <h3 style="font-size:2rem;font-weight:700;margin-bottom:0.5em;">All Time Fave</h3>
        <div style="font-size:1.1rem;font-weight:500;margin-bottom:1.5em;">
            <span>Grande - Php 99</span> &nbsp;|&nbsp; <span>Supreme - Php 120</span>
        </div>
    </div>
    <div class="product-list">
        <?php
        $shownIds = [];
        foreach ($allProducts as $product) {
            if (
                strtolower($product['category']) === 'all time fave'
                && $product['status'] === 'active'
            ) {
                $shownIds[] = $product['id'];
                $imgSrc = $product['image'];
                if (strpos($imgSrc, 'img/') !== 0) {
                    $imgSrc = 'img/' . ltrim($imgSrc, '/');
                }
                // Determine if this is a hot or cold product based on the id or name
                $dataType = (stripos($product['id'], 'hot') !== false || stripos($product['name'], 'hot') !== false) ? 'hot' : 'cold';
                ?>
                <div class="product-item" data-type="<?= $dataType ?>">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <span class="badge bg-primary mb-2">All Time Fave</span>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <div class="product-footer">
                            <button class="view-btn" onclick="handleViewProduct('<?= $product['id'] ?>', '<?= htmlspecialchars($product['name']) ?>', 99, '<?= htmlspecialchars($product['description']) ?>', '<?= htmlspecialchars($imgSrc) ?>')">View</button>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>
<!-- Locations Section -->
<div id="locations" class="section-content location-section">
<section class="locations-hero-header position-relative overflow-hidden">
            <div class="locations-hero-overlay"></div>
            <div class="container-fluid h-100">
                <div class="row h-100 align-items-center justify-content-center text-center text-white">
                    <div class="col-12">
                        <h1 class="locations-hero-title">Our Locations</h1>
                        <p class="locations-hero-subtitle">Crafting moments, one cup at a time</p>
                    </div>
                </div>
            </div>
         <!-- Floating coffee beans -->
         <div class="locations-floating-bean locations-bean-1"></div>
         <div class="locations-floating-bean locations-bean-2"></div>
         <div class="locations-floating-bean locations-bean-3"></div>
     </section>

    
    <?php
    // Fetch all locations and their status from the database
    $conn = new mysqli('localhost', 'root', '', 'ordering');
    $locations = [];
    if (!$conn->connect_error) {
        $res = $conn->query("SELECT * FROM locations ORDER BY id ASC");
        while ($row = $res->fetch_assoc()) {
            $locations[] = $row;
        }
        $conn->close();
    }
    ?>

    <?php foreach ($locations as $loc): ?>
    <div class="container my-5">
        <div class="row bg-light rounded-4 shadow-sm overflow-hidden">
            <!-- Left: Image -->
            <div class="col-md-6 p-0">
                <img src="<?= !empty($loc['image']) ? htmlspecialchars($loc['image']) : 'img/placeholder.png' ?>"
                     alt="<?= htmlspecialchars($loc['name']) ?>" class="img-fluid h-100 w-100 object-fit-cover">
            </div>
            <!-- Right: Info -->
            <div class="col-md-6 d-flex flex-column justify-content-center p-5">
                <small class="text-muted">Lipa City</small>
                <h1 class="fw-bold">Batangas</h1>
                <ul class="list-unstyled mt-4 mb-4">
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($loc['name']) ?></li>
                    <li class="mb-2"><i class="fas fa-clock me-2"></i>3:00 PM - 9:00 PM</li>
                    <li class="mb-2">
                        <i class="fas fa-info me-2"></i>
                        <?= $loc['status'] === 'open' ? '<span style="color:#059669;font-weight:600;">Open</span>' : '<span style="color:#b45309;font-weight:600;">Closed</span>' ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>


<!-- Product Detail Modal -->
<div id="productModal" class="product-modal">
    <button class="product-modal-close-yellow" onclick="closeProductModal()" aria-label="Close">
        &times;
    </button>

    <!-- Main Content -->
    <div class="product-modal-content">
        <div class="product-modal-grid">
            <!-- Product Image -->
            <div class="product-modal-image">
                <img id="modalProductImage" src="/placeholder.svg" alt="">
            </div>
            <!-- Details of Producs -->
            <div class="product-modal-details">
                <h1 id="modalProductName" class="product-modal-title"></h1>
                <p id="modalProductPrice" class="product-modal-price"></p>
                
                <div class="product-modal-description">
                    <h3>Product Description</h3>
                    <p id="modalProductDescription"></p>
                </div>
                <!-- Sizes -->
                <div class="product-modal-sizes">
                    <h3>Size</h3>
                    <div class="size-buttons">
                        <button class="size-btn active" onclick="selectSize('Grande')">Grande</button>
                        <button class="size-btn" onclick="selectSize('Supreme')">Supreme</button>
                    </div>
                </div>
                <!-- Add to Cart Button -->
                <button class="product-modal-add-cart" onclick="addProductToCart()">
                    Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Floating Cart Icon -->
<button class="cart-icon" onclick="openCart()">
    <i class="fas fa-shopping-cart"></i>
    <span class="cart-badge" id="cartCount">0</span>
</button>

<!-- Cart Modal -->
<div id="cartModal" class="cart-modal">
    <div class="cart-content">
        <div class="cart-header">
            <h3>Your Cart</h3>
            <button class="close-cart" onclick="closeCart()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="cartItems" class="cart-items">
            <!-- Cart items will be populated here -->
        </div>
        
        <!-- Delivery Options Section -->
        <div id="deliveryOptions" class="delivery-options" style="display: none;">
            <h4>Pickup Details</h4>
            <div class="form-group">
                <label for="pickupName">Name for Pickup</label>
                <input type="text" id="pickupName" placeholder="Enter your name" required>
            </div>
            <div class="form-group">
                <label for="pickupLocation">Pickup Location</label>
                <select id="pickupLocation" required>
                    <?php
                    // Fetch only open locations from the database
                    $conn = new mysqli('localhost', 'root', '', 'ordering');
                    $pickupLocations = [];
                    if (!$conn->connect_error) {
                        $res = $conn->query("SELECT name FROM locations WHERE status = 'open'");
                        while ($row = $res->fetch_assoc()) {
                            $pickupLocations[] = $row['name'];
                        }
                        $conn->close();
                    }
                    foreach ($pickupLocations as $loc) {
                        echo '<option value="' . htmlspecialchars($loc) . '">' . htmlspecialchars($loc) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="pickupTime">Pickup Time</label>
                <input type="time" id="pickupTime" required>
                <p style="margin-top:6px;font-size:0.95em;color:#b45309;">
                    <strong>Note:</strong> Shop is open for pickup only from 3:00 p.m. to 9:00 p.m.
                </p>
            </div>
            <div class="form-group">
                <label for="specialInstructions">Special Instructions (Optional)</label>
                <textarea id="specialInstructions" rows="2" placeholder="Any special delivery instructions..."></textarea>
            </div>
        </div>

        <div id="cartTotal" class="cart-total">
            <div class="total-container">
                <div id="totalAmount" class="total-amount">Total: $0.00</div>
                <button class="checkout-btn">Checkout</button>
            </div>
        </div>
    </div>
</div>

          <!-- ORDER ONLINE -->
         <section class="food-order-section py-5 text-center" style="background-color:#f3ebd3; color: #2d4a3a; border-radius: 20px; margin: 20px;">
    <div class="container">
        <div class="plain-circle-icon mb-4 mx-auto" style="background-color: #2d4a3a;">
            <i class="fas fa-truck" style="color: #f3ebd3; font-size: 2rem; padding: 10px;"></i>
        </div>
        <h2 class="order-title fw-bold mb-2">Inquire now!</h2>
        <p class="order-subtitle lead mb-4">Be part of our team</p>

        <div class="d-flex flex-wrap justify-content-center gap-3 style">
            <a href="https://www.facebook.com/cupsandcuddles" class="btn order-btn-custom" style="background-color: #2d4a3a;" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-truck me-2"></i> Message Us
            </a>
        </div>
    </div>
</section>


    <footer class="main-footer">
  <div class="footer-container">
    <div class="footer-content-grid">
      <div class="footer-brand">
        <div class="footer-logo-icon">
          <i class="fas fa-mug-hot"></i> 
        </div>
        <h3 class="footer-slogan-text">Life Begins<br>After Coffee</h3>
        <div class="footer-contact">
        </div>
      </div>
      <div class="footer-deliver">
        <h4> ORDER ONLINE</h4>
        <div class="social-icons">
                         
                        <a href="https://www.facebook.com/alaehxpressdeliverymain" class="social-icon" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-truck"></i> 
                        </a>

            </div>
      </div>
      <div class="footer-social">
        <h4>FOLLOW US</h4>
        <div class="social-icons">
                        <a href="https://www.instagram.com/cupsandcuddles.ph" class="social-icon" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-instagram"></i>
                        </a>

                        <a href="https://www.facebook.com/cupsandcuddles" class="social-icon" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-facebook-f"></i>
                        </a>    
                        
            </div>
      </div>
    </div>
    <div class="footer-slogan">
      <h1>CUPS</h1><h3>&</h3><h2>CUDDLES</h2>
    </div>
  </div>
</footer>

    

    <script>
window.PHP_IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
window.PHP_USER_FULLNAME = <?php echo json_encode($userFullName); ?>;
</script>
<script src="js/script.js"></script>
<script src="js/receipt.js"></script>
<script>
    function toggleProfileDropdown(event) {
    event.stopPropagation();
    document.getElementById("profileDropdownMenu").classList.toggle("show");
}
document.addEventListener("click", function() {
    var menu = document.getElementById("profileDropdownMenu");
    if (menu) menu.classList.remove("show");
});

// Add this function to update the dropdown menu after logout
function updateProfileDropdownMenu(isLoggedIn) {
    var menu = document.getElementById("profileDropdownMenu");
    if (!menu) return;
    if (isLoggedIn) {
        menu.innerHTML = `
            <a href="validations/order_history.php" class="dropdown-item">Order History</a>
            <a href="#" class="dropdown-item" onclick="logout(); return false;">Logout</a>
        `;
    } else {
        menu.innerHTML = `
            <a href="#" class="dropdown-item" onclick="showLoginModal(); return false;">Sign In</a>
        `;
    }
}

// Terms and Conditions modal logic
document.addEventListener("DOMContentLoaded", function() {
    var showTermsBtn = document.getElementById('showTermsBtn');
    var termsModal = document.getElementById('termsModal');
    if (showTermsBtn && termsModal) {
        showTermsBtn.onclick = function(e) {
            e.preventDefault();
            termsModal.classList.add('active');
        };
    }
});

// AJAX check for fullname/email existence on registration
document.addEventListener("DOMContentLoaded", function() {
    const registerName = document.getElementById("registerName");
    const registerEmail = document.getElementById("registerEmail");
    const fullnameError = document.getElementById("fullnameError");
    const emailError = document.getElementById("emailError");

    if (registerName) {
        registerName.addEventListener("blur", function() {
            const name = registerName.value.trim();
            if (!name) return;
            fetch('logging/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'fullname=' + encodeURIComponent(name)
            })
            .then(res => res.json())
            .then(data => {
                if (data.exists && data.field === 'fullname') {
                    fullnameError.textContent = data.message;
                } else {
                    fullnameError.textContent = '';
                }
            })
            .catch(err => {
                console.error("Error checking fullname:", err);
                fullnameError.textContent = "An error occurred. Please try again.";
            });
        });
    }

    if (registerEmail) {
        registerEmail.addEventListener("blur", function() {
            const email = registerEmail.value.trim();
            if (!email) return;
            fetch('logging/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(res => res.json())
            .then(data => {
                if (data.exists && data.field === 'email') {
                    emailError.textContent = data.message;
                } else {
                    emailError.textContent = '';
                }
            })
            .catch(err => {
                console.error("Error checking email:", err);
                emailError.textContent = "An error occurred. Please try again.";
            });
        });
    }

    const registerPassword = document.getElementById("registerPassword");
    const passwordError = document.getElementById("passwordError");

    if (registerPassword) {
        registerPassword.addEventListener("blur", function() {
            const password = registerPassword.value.trim();
            if (password.length > 0 && password.length < 8) {
                passwordError.textContent = "Password must be at least 8 characters.";
            } else {
                passwordError.textContent = "";
            }
        });
    }
});

// Debugging login process
document.addEventListener("DOMContentLoaded", function() {
    const loginForm = document.querySelector(".auth-form");
    const loginFullname = document.getElementById("loginFullname");
    const loginPassword = document.getElementById("loginPassword");
    const loginFullnameError = document.getElementById("loginFullnameError");

    if (loginForm) {
        loginForm.addEventListener("submit", function(event) {
            event.preventDefault();
            const fullname = loginFullname.value.trim();
            const password = loginPassword.value.trim();

            fetch('logging/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `fullname=${encodeURIComponent(fullname)}&password=${encodeURIComponent(password)}`
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    console.log("Login successful:", data); // Debugging: Log successful response
                    document.getElementById("profileText").textContent = data.firstName;
                    document.getElementById("profileAvatar").innerHTML = data.initials;
                    let navbarUser = document.querySelector(".navbar-username");
                    if (navbarUser) navbarUser.textContent = data.fullname;
                    document.getElementById("loginSuccess").classList.add("show");
                    setTimeout(() => {
                        closeAuthModal();
                        location.reload();
                    }, 1000);
                } else {
                    loginFullnameError.textContent = data.message || "Login failed. Please check your credentials.";
                    console.error("Login failed:", data); // Debugging: Log failed response
                }
            })
            .catch(err => {
                console.error("Error during login:", err); // Debugging: Log error details
                loginFullnameError.textContent = "An error occurred. Please try again.";
            });
        });
    }
});
</script>
</body>
</html>