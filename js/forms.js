// Form handling for adoption requests
document.addEventListener('DOMContentLoaded', function() {
    const adoptionForm = document.getElementById('adoptionForm');
    if (adoptionForm) {
        adoptionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = {
                name: document.querySelector('#adoptionForm input[placeholder="Your Name"]').value,
                email: document.querySelector('#adoptionForm input[placeholder="Your Email"]').value,
                phone: document.querySelector('#adoptionForm input[placeholder="Your Phone"]').value,
                petCategory: document.querySelector('#adoptionForm select').value,
                reason: document.querySelector('#adoptionForm textarea').value
            };

            // Check if user is logged in
            const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
            if (!isAuthenticated) {
                alert('Please login to submit an adoption request.');
                $('#loginModal').modal('show');
                return;
            }

            // Here you would typically send this data to your backend
            console.log('Adoption request submitted:', formData);
            alert('Thank you for your adoption request! We will contact you soon.');
            adoptionForm.reset();
        });
    }

    // Appointment booking form handling
    const appointmentForm = document.getElementById('appointmentForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Check if user is logged in
            const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
            if (!isAuthenticated) {
                alert('Please login to book an appointment.');
                $('#loginModal').modal('show');
                return;
            }
            
            const user = JSON.parse(localStorage.getItem('user'));
            
            // Get form data
            const petId = document.getElementById('petId').value;
            const clinicId = document.querySelector('[data-bs-target="#appointmentModal"]').getAttribute('data-clinic-id');
            const appointmentDate = document.getElementById('appointmentDate').value;
            const appointmentTime = document.getElementById('appointmentTime').value;
            const reason = document.getElementById('reason').value;
            const selectedServices = Array.from(
                document.querySelectorAll('input[name="service"]:checked')
            ).map(checkbox => parseInt(checkbox.value));
            
            if (selectedServices.length === 0) {
                alert('Please select at least one service');
                return;
            }
            
            try {
                const response = await fetch('api/appointments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        pet_id: petId,
                        clinic_id: clinicId,
                        appointment_date: appointmentDate,
                        appointment_time: appointmentTime,
                        reason: reason,
                        services: selectedServices
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Thank you for booking an appointment! The clinic will confirm your slot soon.');
                    appointmentForm.reset();
                    $('#appointmentModal').modal('hide');
                } else {
                    alert(`Error: ${data.error || 'Failed to book appointment.'}`);
                }
            } catch (error) {
                console.error('Appointment booking error:', error);
                alert('An error occurred during appointment booking. Please try again later.');
            }
        });
    }

    // Login form handling
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Get form data
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                const response = await fetch('api/users.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Login successful
                    auth.isAuthenticated = true;
                    auth.user = data.user;
                    
                    // Store in local storage to persist
                    localStorage.setItem('user', JSON.stringify(auth.user));
                    localStorage.setItem('isAuthenticated', 'true');
                    
                    // Hide modal
                    $('#loginModal').modal('hide');
                    
                    // Update UI
                    updateUIForAuth();
                    
                    // If admin, redirect to admin page
                    if (auth.isAdmin()) {
                        window.location.href = 'admin.html';
                    } else {
                        alert('You have successfully logged in!');
                    }
                } else {
                    alert(`Login failed: ${data.error || 'Invalid credentials'}`);
                }
            } catch (error) {
                console.error('Login error:', error);
                alert('An error occurred during login. Please try again later.');
            }
        });
    }

    // Signup form handling
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const fullName = document.getElementById('fullName').value;
            const email = document.getElementById('signupEmail').value;
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            try {
                const response = await fetch('api/users.php?action=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: fullName,
                        email,
                        password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Account created successfully! You can now login.');
                    $('#signupModal').modal('hide');
                    $('#loginModal').modal('show');
                } else {
                    alert(`Registration failed: ${data.error || 'Something went wrong'}`);
                }
            } catch (error) {
                console.error('Registration error:', error);
                alert('An error occurred during registration. Please try again later.');
            }
        });
    }

    // Check if user is already logged in (page reload)
    if (localStorage.getItem('isAuthenticated') === 'true') {
        const user = JSON.parse(localStorage.getItem('user'));
        if (user) {
            auth.isAuthenticated = true;
            auth.user = user;
            updateUIForAuth();
        }
    }
});

// Authentication state management
class Auth {
    constructor() {
        this.isAuthenticated = false;
        this.user = null;
    }

    async login(email, password) {
        try {
            const response = await fetch('api/users.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.isAuthenticated = true;
                this.user = data.user;
                localStorage.setItem('user', JSON.stringify(this.user));
                localStorage.setItem('isAuthenticated', 'true');
                return this.user;
            } else {
                throw new Error(data.error || 'Invalid credentials');
            }
        } catch (error) {
            console.error('Login error:', error);
            throw new Error('An error occurred during login');
        }
    }

    logout() {
        this.isAuthenticated = false;
        this.user = null;
        // Clear local storage
        localStorage.removeItem('user');
        localStorage.removeItem('isAuthenticated');
        // Redirect to home page
        window.location.href = 'index.html';
    }

    isAdmin() {
        return this.user && this.user.role === 'admin';
    }

    checkAuth() {
        return this.isAuthenticated;
    }
}

// Initialize auth
const auth = new Auth();

// Update UI based on auth state
function updateUIForAuth() {
    const loginLink = document.querySelector('a[data-bs-toggle="modal"][data-bs-target="#loginModal"]');
    if (auth.isAuthenticated && loginLink) {
        loginLink.textContent = 'Logout';
        loginLink.setAttribute('data-bs-toggle', '');
        loginLink.setAttribute('data-bs-target', '');
        loginLink.addEventListener('click', () => auth.logout());
        
        // Add admin link if user is admin
        if (auth.isAdmin()) {
            const navbarNav = document.querySelector('.navbar-nav');
            if (navbarNav && !document.getElementById('adminLink')) {
                const adminLink = document.createElement('a');
                adminLink.id = 'adminLink';
                adminLink.href = 'admin.html';
                adminLink.className = 'nav-item nav-link';
                adminLink.textContent = 'Admin Dashboard';
                navbarNav.insertBefore(adminLink, loginLink);
            }
        }
    }
}

// Call updateUIForAuth when page loads
document.addEventListener('DOMContentLoaded', updateUIForAuth); 