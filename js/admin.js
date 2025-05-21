// Initialize admin dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is admin
    const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
    const user = isAuthenticated ? JSON.parse(localStorage.getItem('user')) : null;
    
    if (!isAuthenticated || !user || user.role !== 'admin') {
        window.location.href = 'index.html';
        return;
    }
    
    // Load data
    loadPets();
    loadCategories();
    loadAdoptionRequests();
    loadAppointments();
    loadUsers();
    loadPricingPlanOrders();
    
    // Setup form event listeners
    const addPetForm = document.getElementById('addPetForm');
    if (addPetForm) {
        addPetForm.addEventListener('submit', handleAddPet);
    }
    
    const addCategoryForm = document.getElementById('addCategoryForm');
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', handleAddCategory);
    }
    
    // Add click event to pricing orders tab
    const pricingOrdersTab = document.getElementById('pricing-orders-tab');
    if (pricingOrdersTab) {
        pricingOrdersTab.addEventListener('click', function() {
            console.log('Pricing orders tab clicked');
            loadPricingPlanOrders();
        });
    }
});

// Load data functions
async function loadPets() {
    try {
        const response = await fetch('api/pets.php');
        const data = await response.json();
        
        if (data.success) {
            const pets = data.data;
            const tbody = document.getElementById('petsTableBody');
            
            if (!tbody) {
                console.error('Pets table body not found');
                return;
            }
            
            tbody.innerHTML = pets.map(pet => `
                <tr>
                    <td>${pet.id}</td>
                    <td>${pet.name}</td>
                    <td>${pet.category_name || 'Uncategorized'}</td>
                    <td>${pet.age || '-'}</td>
                    <td>${pet.gender}</td>
                    <td>${pet.is_available ? 'Available' : 'Adopted'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editPet(${pet.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deletePet(${pet.id})">Delete</button>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="7" class="text-center">No pets found</td></tr>';
        } else {
            console.error('Error loading pets:', data.error);
            document.getElementById('petsTableBody').innerHTML = '<tr><td colspan="7" class="text-center">Error loading pets</td></tr>';
        }
    } catch (error) {
        console.error('Error loading pets:', error);
        document.getElementById('petsTableBody').innerHTML = '<tr><td colspan="7" class="text-center">Error loading pets</td></tr>';
    }
}

async function loadCategories() {
    try {
        const response = await fetch('api/categories.php');
        const data = await response.json();
        
        if (data.success) {
            const categories = data.data;
            const tbody = document.getElementById('categoriesTableBody');
            
            if (!tbody) {
                console.error('Categories table body not found');
                return;
            }
            
            tbody.innerHTML = categories.map(category => `
                <tr>
                    <td>${category.id}</td>
                    <td>${category.name}</td>
                    <td>${category.description || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editCategory(${category.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteCategory(${category.id})">Delete</button>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="4" class="text-center">No categories found</td></tr>';
            
            // Also update the category select in add pet form
            const select = document.querySelector('#addPetForm select[name="category"]');
            if (select) {
                select.innerHTML = '<option value="">Select Category</option>' + 
                    categories.map(category => `
                        <option value="${category.id}">${category.name}</option>
                    `).join('');
            }
        } else {
            console.error('Error loading categories:', data.error);
            document.getElementById('categoriesTableBody').innerHTML = '<tr><td colspan="4" class="text-center">Error loading categories</td></tr>';
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        document.getElementById('categoriesTableBody').innerHTML = '<tr><td colspan="4" class="text-center">Error loading categories</td></tr>';
    }
}

async function loadAdoptionRequests() {
    try {
        const response = await fetch('api/adoptions.php');
        const data = await response.json();
        
        if (data.success) {
            const requests = data.data;
            const tbody = document.getElementById('adoptionsTableBody');
            
            if (!tbody) {
                console.error('Adoptions table body not found');
                return;
            }
            
            tbody.innerHTML = requests.map(request => `
                <tr>
                    <td>${request.id}</td>
                    <td>${request.pet_name}</td>
                    <td>${request.user_name}</td>
                    <td>${new Date(request.created_at).toLocaleDateString()}</td>
                    <td>${request.status}</td>
                    <td>
                        ${request.status === 'pending' ? `
                            <button class="btn btn-sm btn-success" onclick="approveAdoption(${request.id})">Approve</button>
                            <button class="btn btn-sm btn-danger" onclick="rejectAdoption(${request.id})">Reject</button>
                        ` : '-'}
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="6" class="text-center">No adoption requests found</td></tr>';
        } else {
            console.error('Error loading adoption requests:', data.error);
            document.getElementById('adoptionsTableBody').innerHTML = '<tr><td colspan="6" class="text-center">Error loading adoption requests</td></tr>';
        }
    } catch (error) {
        console.error('Error loading adoption requests:', error);
        document.getElementById('adoptionsTableBody').innerHTML = '<tr><td colspan="6" class="text-center">Error loading adoption requests</td></tr>';
    }
}

async function loadAppointments() {
    try {
        console.log('Loading appointments...');
        
        // Show loading indicator
        const appointmentsTableBody = document.getElementById('appointmentsTableBody');
        if (!appointmentsTableBody) {
            console.error('Appointments table body element not found in the DOM');
            return;
        }
        
        appointmentsTableBody.innerHTML = '<tr><td colspan="8" class="text-center">Loading appointments...</td></tr>';
        
        const response = await fetch('api/appointments.php');
        console.log('Appointments API Response status:', response.status);
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Appointments API Data:', data);
        
        if (data.success) {
            let html = '';
            
            if (!data.data || data.data.length === 0) {
                appointmentsTableBody.innerHTML = '<tr><td colspan="8" class="text-center">No appointments found</td></tr>';
                return;
            }
            
            console.log(`Found ${data.data.length} appointments`);
            
            data.data.forEach(appointment => {
                console.log('Processing appointment:', appointment);
                
                const statusBadgeClass = 
                    appointment.status === 'scheduled' ? 'badge bg-primary' :
                    appointment.status === 'completed' ? 'badge bg-success' :
                    appointment.status === 'cancelled' ? 'badge bg-danger' : 'badge bg-secondary';
                
                let servicesHtml = '';
                if (appointment.services && appointment.services.length > 0) {
                    servicesHtml = appointment.services.map(service => 
                        `<span class="badge bg-info me-1">${service.name} ($${service.price})</span>`
                    ).join(' ');
                } else {
                    servicesHtml = '<span class="text-muted">No services</span>';
                }
                
                html += `
                <tr>
                    <td>${appointment.id}</td>
                    <td>${appointment.pet_name || 'N/A'}</td>
                    <td>${appointment.first_name || ''} ${appointment.last_name || ''}</td>
                    <td>${appointment.clinic_name || 'N/A'}</td>
                    <td>${appointment.appointment_date || 'N/A'} ${appointment.appointment_time || ''}</td>
                    <td>${servicesHtml}</td>
                    <td><span class="${statusBadgeClass}">${appointment.status || 'unknown'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="updateAppointment(${appointment.id}, 'completed')">Complete</button>
                        <button class="btn btn-sm btn-danger" onclick="updateAppointment(${appointment.id}, 'cancelled')">Cancel</button>
                    </td>
                </tr>
                `;
            });
            
            appointmentsTableBody.innerHTML = html;
            console.log('Appointments loaded successfully');
        } else {
            console.error('Error loading appointments:', data.error);
            appointmentsTableBody.innerHTML = 
                '<tr><td colspan="8" class="text-center">Error loading appointments: ' + (data.error || 'Unknown error') + '</td></tr>';
        }
    } catch (error) {
        console.error('Error fetching appointments:', error);
        const appointmentsTableBody = document.getElementById('appointmentsTableBody');
        if (appointmentsTableBody) {
            appointmentsTableBody.innerHTML = 
                '<tr><td colspan="8" class="text-center">Error fetching appointments: ' + error.message + '</td></tr>';
        }
    }
}

async function loadUsers() {
    try {
        const response = await fetch('api/users.php');
        const data = await response.json();
        
        if (data.success) {
            const users = data.data;
            const tbody = document.getElementById('usersTableBody');
            
            if (!tbody) {
                console.error('Users table body not found');
                return;
            }
            
            tbody.innerHTML = users.map(user => `
                <tr>
                    <td>${user.id}</td>
                    <td>${user.name}</td>
                    <td>${user.email}</td>
                    <td>${user.role}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Delete</button>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="5" class="text-center">No users found</td></tr>';
        } else {
            console.error('Error loading users:', data.error);
            document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="5" class="text-center">Error loading users</td></tr>';
        }
    } catch (error) {
        console.error('Error loading users:', error);
        document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="5" class="text-center">Error loading users</td></tr>';
    }
}

async function loadPricingPlanOrders() {
    try {
        console.log('Loading pricing plan orders...');
        
        // Show loading indicator
        const tbody = document.getElementById('pricingPlanOrdersTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading pricing plan orders...</td></tr>';
        }
        
        const response = await fetch('api/pricing_orders.php');
        console.log('API Response status:', response.status);
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Data:', data);
        
        if (!tbody) {
            console.error('Pricing plan orders table body not found');
            return;
        }
        
        if (data.success) {
            const orders = data.data || [];
            console.log('Orders:', orders);
            
            if (orders && orders.length > 0) {
                tbody.innerHTML = orders.map(order => `
                    <tr>
                        <td>${order.id || 'N/A'}</td>
                        <td>${order.user_name || 'Unknown'}</td>
                        <td>${order.plan_name || 'Unknown'}</td>
                        <td>$${order.price || '0.00'}</td>
                        <td>${order.order_date ? new Date(order.order_date).toLocaleDateString() : 'N/A'}</td>
                        <td>
                            <span class="badge ${order.status === 'pending' ? 'bg-warning' : 
                                              order.status === 'active' ? 'bg-success' : 
                                              order.status === 'cancelled' ? 'bg-danger' : 'bg-secondary'}">
                                ${order.status || 'pending'}
                            </span>
                        </td>
                        <td>
                            ${order.status === 'pending' ? `
                                <button class="btn btn-sm btn-success" onclick="approvePricingOrder(${order.id})">Approve</button>
                                <button class="btn btn-sm btn-danger" onclick="cancelPricingOrder(${order.id})">Cancel</button>
                            ` : '-'}
                        </td>
                    </tr>
                `).join('');
            } else {
                console.log('No orders found');
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No pricing plan orders found</td></tr>';
            }
        } else {
            console.error('Error loading pricing plan orders:', data.error);
            tbody.innerHTML = 
                '<tr><td colspan="7" class="text-center">Error loading pricing plan orders: ' + (data.error || 'Unknown error') + '</td></tr>';
        }
    } catch (error) {
        console.error('Error loading pricing plan orders:', error);
        const tbody = document.getElementById('pricingPlanOrdersTableBody');
        if (tbody) {
            tbody.innerHTML = 
                '<tr><td colspan="7" class="text-center">Error loading pricing plan orders: ' + error.message + '</td></tr>';
        }
    }
}

// Form handlers
async function handleAddPet(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const pet = {
        name: formData.get('name'),
        category_id: formData.get('category'),
        breed: formData.get('breed'),
        age: formData.get('age'),
        gender: formData.get('gender'),
        description: formData.get('description'),
        is_available: true
    };
    
    // Handle image upload if available
    if (formData.get('image').size > 0) {
        // This would need a separate endpoint for file uploads
        // For now, just use a default image path
        pet.image_url = 'img/dog.jpg';
    }

    try {
        const response = await fetch('api/pets.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(pet)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Pet added successfully!');
            $('#addPetModal').modal('hide');
            e.target.reset();
            loadPets();
        } else {
            alert(`Failed to add pet: ${data.error}`);
        }
    } catch (error) {
        console.error('Error adding pet:', error);
        alert('An error occurred while adding the pet. Please try again later.');
    }
}

async function handleAddCategory(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const category = {
        name: formData.get('name'),
        description: formData.get('description')
    };

    try {
        const response = await fetch('api/categories.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(category)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Category added successfully!');
            $('#addCategoryModal').modal('hide');
            e.target.reset();
            loadCategories();
        } else {
            alert(`Failed to add category: ${data.error}`);
        }
    } catch (error) {
        console.error('Error adding category:', error);
        alert('An error occurred while adding the category. Please try again later.');
    }
}

// Action handlers
async function editPet(id) {
    try {
        const response = await fetch(`api/pets.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const pet = data.data;
            
            // Populate edit form with pet data
            // This assumes you have an edit modal with a form
            document.getElementById('editPetId').value = pet.id;
            document.getElementById('editPetName').value = pet.name;
            document.getElementById('editPetCategory').value = pet.category_id;
            document.getElementById('editPetBreed').value = pet.breed || '';
            document.getElementById('editPetAge').value = pet.age || '';
            document.getElementById('editPetGender').value = pet.gender;
            document.getElementById('editPetDescription').value = pet.description || '';
            document.getElementById('editPetStatus').checked = pet.is_available === 1;
            
            // Show edit modal
            $('#editPetModal').modal('show');
        } else {
            alert(`Error fetching pet details: ${data.error}`);
        }
    } catch (error) {
        console.error('Error fetching pet details:', error);
        alert('An error occurred while fetching pet details. Please try again later.');
    }
}

async function deletePet(id) {
    if (confirm('Are you sure you want to delete this pet?')) {
        try {
            const response = await fetch(`api/pets.php?id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Pet deleted successfully!');
                loadPets();
            } else {
                alert(`Failed to delete pet: ${data.error}`);
            }
        } catch (error) {
            console.error('Error deleting pet:', error);
            alert('An error occurred while deleting the pet. Please try again later.');
        }
    }
}

async function editCategory(id) {
    try {
        const response = await fetch(`api/categories.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const category = data.data;
            
            // Populate edit form with category data
            document.getElementById('editCategoryId').value = category.id;
            document.getElementById('editCategoryName').value = category.name;
            document.getElementById('editCategoryDescription').value = category.description || '';
            
            // Show edit modal
            $('#editCategoryModal').modal('show');
        } else {
            alert(`Error fetching category details: ${data.error}`);
        }
    } catch (error) {
        console.error('Error fetching category details:', error);
        alert('An error occurred while fetching category details. Please try again later.');
    }
}

async function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category? This will also affect all pets in this category.')) {
        try {
            const response = await fetch(`api/categories.php?id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Category deleted successfully!');
                loadCategories();
            } else {
                alert(`Failed to delete category: ${data.error}`);
            }
        } catch (error) {
            console.error('Error deleting category:', error);
            alert('An error occurred while deleting the category. Please try again later.');
        }
    }
}

async function approveAdoption(id) {
    try {
        const response = await fetch('api/adoptions.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id,
                status: 'approved'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Adoption request approved successfully!');
            loadAdoptionRequests();
            loadPets(); // Refresh pets as availability has changed
        } else {
            alert(`Failed to approve adoption request: ${data.error}`);
        }
    } catch (error) {
        console.error('Error approving adoption request:', error);
        alert('An error occurred while approving the adoption request. Please try again later.');
    }
}

async function rejectAdoption(id) {
    if (confirm('Are you sure you want to reject this adoption request?')) {
        try {
            const response = await fetch('api/adoptions.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id,
                    status: 'rejected'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Adoption request rejected successfully!');
                loadAdoptionRequests();
            } else {
                alert(`Failed to reject adoption request: ${data.error}`);
            }
        } catch (error) {
            console.error('Error rejecting adoption request:', error);
            alert('An error occurred while rejecting the adoption request. Please try again later.');
        }
    }
}

async function updateAppointment(id, status) {
    if (!confirm(`Are you sure you want to mark this appointment as ${status}?`)) {
        return;
    }
    
    try {
        const response = await fetch('api/appointments.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id,
                status
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`Appointment ${status} successfully`);
            loadAppointments();
        } else {
            alert(`Error: ${data.error}`);
        }
    } catch (error) {
        console.error('Error updating appointment:', error);
        alert('An error occurred. Please try again later.');
    }
}

async function editUser(id) {
    try {
        const response = await fetch(`api/users.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const user = data.data;
            
            // Populate edit form with user data
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUserName').value = user.name;
            document.getElementById('editUserEmail').value = user.email;
            document.getElementById('editUserPhone').value = user.phone || '';
            document.getElementById('editUserRole').value = user.role;
            
            // Show edit modal
            $('#editUserModal').modal('show');
        } else {
            alert(`Error fetching user details: ${data.error}`);
        }
    } catch (error) {
        console.error('Error fetching user details:', error);
        alert('An error occurred while fetching user details. Please try again later.');
    }
}

async function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user? This will also delete all their adoption requests and appointments.')) {
        try {
            const response = await fetch(`api/users.php?id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('User deleted successfully!');
                loadUsers();
            } else {
                alert(`Failed to delete user: ${data.error}`);
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            alert('An error occurred while deleting the user. Please try again later.');
        }
    }
}

async function approvePricingOrder(id) {
    if (confirm('Are you sure you want to approve this pricing plan order?')) {
        try {
            console.log('Approving pricing plan order:', id);
            
            // Create a simple object with the required data
            const updateData = {
                id: id,
                status: 'active'
            };
            
            console.log('Sending update data:', updateData);
            
            const response = await fetch('api/pricing_orders.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(updateData)
            });
            
            console.log('API Response status:', response.status);
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('API Data:', data);
            
            if (data.success) {
                alert('Pricing plan order approved successfully!');
                loadPricingPlanOrders(); // Reload the orders
            } else {
                alert(`Failed to approve pricing plan order: ${data.error || 'Unknown error'}`);
            }
        } catch (error) {
            console.error('Error approving pricing plan order:', error);
            alert('An error occurred while approving the pricing plan order: ' + error.message);
        }
    }
}

async function cancelPricingOrder(id) {
    if (confirm('Are you sure you want to cancel this pricing plan order?')) {
        try {
            console.log('Cancelling pricing plan order:', id);
            
            // Create a simple object with the required data
            const updateData = {
                id: id,
                status: 'cancelled'
            };
            
            console.log('Sending update data:', updateData);
            
            const response = await fetch('api/pricing_orders.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(updateData)
            });
            
            console.log('API Response status:', response.status);
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('API Data:', data);
            
            if (data.success) {
                alert('Pricing plan order cancelled successfully!');
                loadPricingPlanOrders(); // Reload the orders
            } else {
                alert(`Failed to cancel pricing plan order: ${data.error || 'Unknown error'}`);
            }
        } catch (error) {
            console.error('Error cancelling pricing plan order:', error);
            alert('An error occurred while cancelling the pricing plan order: ' + error.message);
        }
    }
} 