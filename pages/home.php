<!-- Hero Section -->
<section class="hero-section d-flex align-items-center bg-light text-center text-light">
    <div class="container">
        <h1 class="display-1 fw-bold animate-fadeIn">
            <img src="./assets/images/logo-white.png" alt="Resort Rental Logo" class="img-fluid" style="max-height: 20rem;">
        </h1>
        <p class="lead mb-4 animate-fadeIn delay-1s">
            Resort Venue Rental Appointment Booking System
        </p>

        <div class="gap-2 flex-sm-row mb-5">
            <p class="lead mb-4 animate-fadeIn delay-2s hero-sub-content">
                Discover and book the perfect resort venue for your special events with ease and confidence.
            </p>
            <p class="lead animate-fadeIn delay-2s hero-sub-content">Your perfect destination for weddings, parties, and celebrations</p>
            <p class="lead animate-fadeIn delay-2s hero-sub-content">Experience luxury venues, tailored services, and unforgettable moments</p>
        </div>

        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center animate-fadeIn delay-2s">
            <a href="index.php?page=booking" class="btn btn-primary btn-lg px-5 shadow-lg hover-scale">
                <i class="fas fa-calendar-check me-2"></i> Book Your Resort
            </a>
            <a href="index.php?page=venue" class="btn btn-outline-light btn-lg px-5 hover-scale">
                <i class="fas fa-map-marker-alt me-2"></i> Explore Resorts
            </a>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row align-items-center about-section-style">
            <div class="col-lg-6">
                <h2 class="display-4 mb-4">Find the Perfect Venue</h2>
                <p class="lead mb-4">
                    Discover a wide selection of premier venues across the Philippines. Whether you’re
                    planning an elegant ballroom celebration, an outdoor garden wedding, or a professional
                    conference, our platform connects you to the best locations nationwide — tailored to
                    make your event truly unforgettable.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="feature-icon  bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="fas fa-map-marker-alt text-primary fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Nationwide Choices</h5>
                                <small class="text-muted">Venues from Luzon, Visayas, and Mindanao</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="feature-icon  bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="fas fa-calendar-check text-primary fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Event Ready</h5>
                                <small class="text-muted">Perfect spaces for weddings, parties & corporate events</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="feature-icon  bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="fas fa-star text-primary fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Premium Service</h5>
                                <small class="text-muted">Trusted venues with quality amenities</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="feature-icon  bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="fas fa-users text-primary fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Seamless Booking</h5>
                                <small class="text-muted">Easy and reliable reservation process</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Image (landscape-fixed wrapper) -->
            <div class="col-lg-6">
                <div class="image-wrapper">
                    <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080"
                        alt="Philippine venue"
                        class="img-fluid rounded-3"
                        onerror="this.onerror=null; this.src='assets/images/no-image.png';">
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Featured Venues -->
<section class="py-5">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="display-4 mb-4">Our Featured Venues</h2>
            <p class="lead text-muted">
                Discover our collection of exceptional venues, each designed to create magical moments
            </p>
        </div>

        <div class="row g-4" id="featuredVenues">
            <!-- Venues will be loaded by JavaScript -->
        </div>

        <div class="text-center mt-5">
            <a class="btn btn-primary btn-lg <?= $page === 'venue' ? 'active' : '' ?>" href="index.php?page=venue">View All Venues</a>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-6">
                <h2 class="display-4 mb-4">Get In Touch</h2>
                <p class="lead mb-4">
                    Ready to plan your perfect event? Our team is here to help you every step of the way.
                </p>

                <div class="contact-info">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-phone text-primary me-3"></i>
                        <span>+1 (555) 123-4567</span>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-envelope text-primary me-3"></i>
                        <span>events@paradiseresort.com</span>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-map-marker-alt text-primary me-3"></i>
                        <span>123 Paradise Bay, Tropical Island</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="bg-white p-4 rounded-3 shadow-sm">
                    <h3 class="mb-4">Quick Inquiry</h3>
                    <form id="contactForm">
                        <div class="mb-3">
                            <label for="contactName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="contactName" name="name" placeholder="Your name" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="contactEmail" name="email" placeholder="your@email.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactMessage" class="form-label">Message</label>
                            <textarea class="form-control" id="contactMessage" name="message" rows="4"
                                placeholder="Tell us about your event..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>