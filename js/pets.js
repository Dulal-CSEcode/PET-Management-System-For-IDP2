// Fetch pet data from API
let petsData = {
    dogs: [],
    cats: [],
    birds: [],
    others: []
};

// Function to fetch pets by category
async function fetchPetsByCategory(category) {
    try {
        const response = await fetch(`api/pets.php?category=${category}`);
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            console.error('Error fetching pets:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Error fetching pets:', error);
        return [];
    }
}

// Initialize pet data
async function initializePetData() {
    try {
        const [dogs, cats, birds, others] = await Promise.all([
            fetchPetsByCategory('Dogs'),
            fetchPetsByCategory('Cats'),
            fetchPetsByCategory('Birds'),
            fetchPetsByCategory('Others')
        ]);
        
        petsData.dogs = dogs;
        petsData.cats = cats;
        petsData.birds = birds;
        petsData.others = others;
        
        // Once data is loaded, check which page we're on and generate content
        const currentUrl = window.location.href;
        
        if (currentUrl.includes('dogs.html')) {
            generatePetCards('dogs');
        } else if (currentUrl.includes('cats.html')) {
            generatePetCards('cats');
        } else if (currentUrl.includes('birds.html')) {
            generatePetCards('birds');
        } else if (currentUrl.includes('other-pets.html')) {
            generatePetCards('others');
        }
    } catch (error) {
        console.error('Error initializing pet data:', error);
        
        // Fallback to sample data if API fails
        petsData = {
            dogs: [
                {
                    id: 1,
                    name: "Max",
                    breed: "Labrador Retriever",
                    age: 2,
                    gender: "Male",
                    description: "Friendly and energetic dog who loves to play fetch and go for long walks. Great with children and other pets.",
                    image_url: "img/dogs/Dog 1.png",
                    is_available: 1
                },
                {
                    id: 2,
                    name: "Buddy",
                    breed: "German Shepherd",
                    age: 3,
                    gender: "Male",
                    description: "Intelligent and loyal dog with excellent guard dog capabilities. Well-trained and obedient.",
                    image_url: "img/dogs/Dog 3.png",
                    is_available: 1
                }
            ],
            cats: [
                {
                    id: 3,
                    name: "Luna",
                    breed: "Siamese",
                    age: 2,
                    gender: "Female",
                    description: "Elegant and vocal cat who loves attention. Enjoys sitting in laps and playing with string toys.",
                    image_url: "img/cats/Cat 1.jpg",
                    is_available: 1
                },
                {
                    id: 4,
                    name: "Oliver",
                    breed: "Maine Coon",
                    age: 1,
                    gender: "Male",
                    description: "Large and fluffy cat with a gentle demeanor. Very affectionate and gets along well with other pets.",
                    image_url: "img/cats/Cat 7.png",
                    is_available: 1
                }
            ],
            birds: [
                {
                    id: 5,
                    name: "Sunny",
                    breed: "Canary",
                    age: 1,
                    gender: "Male",
                    description: "Bright yellow canary with a beautiful singing voice. Brings joy and music to any home.",
                    image_url: "img/birds/Bird 1.png",
                    is_available: 1
                },
                {
                    id: 6,
                    name: "Blue",
                    breed: "Budgerigar",
                    age: 2,
                    gender: "Male",
                    description: "Colorful and playful budgie who loves to chirp and interact with people. Can learn to mimic words.",
                    image_url: "img/birds/Bird 2.png",
                    is_available: 1
                }
            ],
            others: [
                {
                    id: 7,
                    name: "Thumper",
                    breed: "Holland Lop Rabbit",
                    age: 1,
                    gender: "Male",
                    description: "Friendly rabbit with floppy ears, great for families. Loves to hop around and eat fresh vegetables.",
                    image_url: "img/others/Rabit 1.jpg",
                    is_available: 1
                },
                {
                    id: 8,
                    name: "Shelly",
                    breed: "Red-Eared Slider Turtle",
                    age: 3,
                    gender: "Female",
                    description: "Calm and easy to care for turtle, perfect for beginners. Enjoys basking and swimming.",
                    image_url: "img/others/tortos1.png",
                    is_available: 1
                }
            ]
        };
        
        // Once fallback data is set, check which page we're on and generate content
        const currentUrl = window.location.href;
        
        if (currentUrl.includes('dogs.html')) {
            generatePetCards('dogs');
        } else if (currentUrl.includes('cats.html')) {
            generatePetCards('cats');
        } else if (currentUrl.includes('birds.html')) {
            generatePetCards('birds');
        } else if (currentUrl.includes('other-pets.html')) {
            generatePetCards('others');
        }
    }
}

// Function to generate pet cards
function generatePetCards(petType) {
    const pets = petsData[petType];
    const container = document.getElementById(`${petType}-container`);
    
    if (!container) {
        console.error(`Container for ${petType} not found`);
        return;
    }
    
    let html = '';
    
    if (pets && pets.length > 0) {
        pets.forEach(pet => {
            const status = pet.is_available ? 'Available' : 'Adopted';
            const statusClass = pet.is_available ? 'text-success' : 'text-danger';
            const imageUrl = pet.image_url || `img/${petType.slice(0, -1)}.jpg`; // Fallback image
            
            html += `
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <img class="card-img-top" src="${imageUrl}" alt="${pet.name}">
                    <div class="card-body">
                        <h4 class="card-title">${pet.name}</h4>
                        <p class="card-text"><strong>Breed:</strong> ${pet.breed}</p>
                        <p class="card-text"><strong>Age:</strong> ${pet.age} ${pet.age === 1 ? 'year' : 'years'}</p>
                        <p class="card-text"><strong>Gender:</strong> ${pet.gender}</p>
                        <p class="card-text">${pet.description}</p>
                        <p class="card-text"><strong>Status:</strong> <span class="${statusClass}">${status}</span></p>
                    </div>
                    <div class="card-footer">
                        <a href="#" class="btn btn-primary btn-adopt ${!pet.is_available ? 'disabled' : ''}" 
                           data-pet-id="${pet.id}" 
                           data-pet-type="${petType}" 
                           data-bs-toggle="modal" 
                           data-bs-target="#adoptionModal"
                           ${!pet.is_available ? 'aria-disabled="true"' : ''}>
                           ${pet.is_available ? `Adopt ${pet.name}` : 'Already Adopted'}
                        </a>
                    </div>
                </div>
            </div>
            `;
        });
    } else {
        html = '<div class="col-12"><p class="text-center">No pets available at the moment.</p></div>';
    }
    
    container.innerHTML = html;
    
    // Add event listeners for adopt buttons
    const adoptButtons = document.querySelectorAll('.btn-adopt:not(.disabled)');
    adoptButtons.forEach(button => {
        button.addEventListener('click', function() {
            const petId = this.getAttribute('data-pet-id');
            const petType = this.getAttribute('data-pet-type');
            const pet = petsData[petType].find(p => p.id == petId);
            
            if (pet) {
                document.getElementById('adoption-pet-name').textContent = pet.name;
                document.getElementById('adoption-pet-type').textContent = petType.slice(0, -1);
                document.getElementById('selected-pet-id').value = petId;
                document.getElementById('selected-pet-type').value = petType;
            }
        });
    });
}

// Function to submit adoption request
async function submitAdoptionRequest(petId, petType, reason) {
    try {
        // Get user from localStorage (set by forms.js login)
        const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
        const user = JSON.parse(localStorage.getItem('user'));
        
        if (!isAuthenticated || !user) {
            alert('Please login to submit an adoption request.');
            $('#adoptionModal').modal('hide');
            $('#loginModal').modal('show');
            return false;
        }
        
        const response = await fetch('api/adoptions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                pet_id: petId,
                user_id: user.id,
                reason: reason
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Thank you for your adoption request! We will contact you soon.');
            return true;
        } else {
            alert(`Error: ${data.error || 'Failed to submit adoption request.'}`);
            return false;
        }
    } catch (error) {
        console.error('Error submitting adoption request:', error);
        alert('An error occurred. Please try again later.');
        return false;
    }
}

// Initialize pet listings when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize pet data
    initializePetData();
    
    // Add event listeners for adoption buttons on adoption.html
    const dogButton = document.querySelector('a[href="#"].btn-primary[data-category="dog"]');
    const catButton = document.querySelector('a[href="#"].btn-primary[data-category="cat"]');
    const birdButton = document.querySelector('a[href="#"].btn-primary[data-category="bird"]');
    
    if (dogButton) {
        dogButton.href = 'dogs.html';
    }
    
    if (catButton) {
        catButton.href = 'cats.html';
    }
    
    if (birdButton) {
        birdButton.href = 'birds.html';
    }
    
    // Handle adoption form submission
    const adoptionSubmitForm = document.getElementById('adoptionSubmitForm');
    if (adoptionSubmitForm) {
        adoptionSubmitForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const petId = document.getElementById('selected-pet-id').value;
            const petType = document.getElementById('selected-pet-type').value;
            const reason = document.getElementById('adoption-reason').value;
            
            const success = await submitAdoptionRequest(petId, petType, reason);
            
            if (success) {
                $('#adoptionModal').modal('hide');
                adoptionSubmitForm.reset();
            }
        });
    }
}); 