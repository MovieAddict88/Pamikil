// Menu Toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('hidden');
    mainContent.classList.toggle('expanded');
});

// Section Navigation
const menuItems = document.querySelectorAll('.menu-item[data-section]');
const sections = document.querySelectorAll('.section');

menuItems.forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        const sectionName = item.dataset.section;
        showSection(sectionName);
        
        menuItems.forEach(mi => mi.classList.remove('active'));
        item.classList.add('active');
        
        if (window.innerWidth < 769) {
            sidebar.classList.add('hidden');
            mainContent.classList.add('expanded');
        }
    });
});

function showSection(sectionName) {
    sections.forEach(section => section.classList.add('hidden'));
    
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.remove('hidden');
        updatePageTitle(sectionName);
        
        switch(sectionName) {
            case 'content':
                loadContent();
                break;
            case 'categories':
                loadCategories();
                break;
        }
    }
}

function updatePageTitle(section) {
    const titles = {
        'dashboard': 'Dashboard',
        'content': 'Manage Content',
        'add-content': 'Add New Content',
        'tmdb': 'Import from TMDB',
        'categories': 'Categories',
        'settings': 'Settings'
    };
    document.getElementById('pageTitle').textContent = titles[section] || section;
}

// Load Content
async function loadContent() {
    const search = document.getElementById('contentSearch').value;
    const type = document.getElementById('contentTypeFilter').value;
    
    try {
        const response = await fetch(`api.php?action=list_content&search=${encodeURIComponent(search)}&type=${type}`);
        const data = await response.json();
        
        if (data.success) {
            renderContentTable(data.data);
        }
    } catch (error) {
        console.error('Error loading content:', error);
    }
}

function renderContentTable(content) {
    const tbody = document.getElementById('contentTableBody');
    
    if (content.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No content found</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    content.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <img src="${item.poster_url || '../assets/images/placeholder.jpg'}" alt="${escapeHtml(item.title)}" style="width: 50px; height: 75px; object-fit: cover; border-radius: 4px;">
            </td>
            <td>${escapeHtml(item.title)}</td>
            <td><span style="text-transform: uppercase; font-size: 0.85em; padding: 4px 8px; border-radius: 4px; background: var(--surface-light);">${item.type}</span></td>
            <td>${item.year || '-'}</td>
            <td>${item.views || 0}</td>
            <td class="table-actions">
                <button class="btn btn-secondary btn-sm" onclick="editContent(${item.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteContent(${item.id}, '${escapeHtml(item.title)}')">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

document.getElementById('contentSearch').addEventListener('input', () => {
    loadContent();
});

document.getElementById('contentTypeFilter').addEventListener('change', () => {
    loadContent();
});

// Add Content Form
const addContentForm = document.getElementById('addContentForm');
addContentForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(addContentForm);
    formData.append('action', 'add_content');
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            addContentForm.reset();
            showSection('content');
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        showAlert('danger', 'Error adding content');
        console.error('Error:', error);
    }
});

// Edit Content
async function editContent(id) {
    try {
        const response = await fetch(`api.php?action=get_content&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const content = data.data;
            
            const formFields = `
                <div class="form-row">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" value="${escapeHtml(content.title)}" required>
                    </div>
                    <div class="form-group">
                        <label>Type *</label>
                        <select name="type" required>
                            <option value="movie" ${content.type === 'movie' ? 'selected' : ''}>Movie</option>
                            <option value="series" ${content.type === 'series' ? 'selected' : ''}>Series</option>
                            <option value="live" ${content.type === 'live' ? 'selected' : ''}>Live</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" name="year" value="${content.year || ''}">
                    </div>
                    <div class="form-group">
                        <label>Rating</label>
                        <input type="number" name="rating" value="${content.rating || ''}" step="0.1">
                    </div>
                    <div class="form-group">
                        <label>Duration</label>
                        <input type="text" name="duration" value="${escapeHtml(content.duration || '')}">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4">${escapeHtml(content.description || '')}</textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Genre</label>
                        <input type="text" name="genre" value="${escapeHtml(content.genre || '')}">
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" value="${escapeHtml(content.country || '')}">
                    </div>
                    <div class="form-group">
                        <label>Language</label>
                        <input type="text" name="language" value="${escapeHtml(content.language || '')}">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Poster URL</label>
                        <input type="url" name="poster_url" value="${escapeHtml(content.poster_url || '')}">
                    </div>
                    <div class="form-group">
                        <label>Backdrop URL</label>
                        <input type="url" name="backdrop_url" value="${escapeHtml(content.backdrop_url || '')}">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" id="editCategorySelect">
                            <option value="">None</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Featured</label>
                        <select name="featured">
                            <option value="0" ${content.featured == 0 ? 'selected' : ''}>No</option>
                            <option value="1" ${content.featured == 1 ? 'selected' : ''}>Yes</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Content
                </button>
            `;
            
            document.getElementById('editFormFields').innerHTML = formFields;
            document.getElementById('editId').value = id;
            
            loadCategoriesForSelect('editCategorySelect', content.category_id);
            
            document.getElementById('editModal').classList.add('active');
        }
    } catch (error) {
        console.error('Error loading content:', error);
    }
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

const editContentForm = document.getElementById('editContentForm');
editContentForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(editContentForm);
    formData.append('action', 'update_content');
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            closeEditModal();
            loadContent();
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        showAlert('danger', 'Error updating content');
        console.error('Error:', error);
    }
});

// Delete Content
async function deleteContent(id, title) {
    if (!confirm(`Are you sure you want to delete "${title}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_content');
    formData.append('id', id);
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            loadContent();
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        showAlert('danger', 'Error deleting content');
        console.error('Error:', error);
    }
}

// Load Categories
async function loadCategories() {
    try {
        const response = await fetch('api.php?action=list_categories');
        const data = await response.json();
        
        if (data.success) {
            renderCategoriesTable(data.data);
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

function renderCategoriesTable(categories) {
    const tbody = document.getElementById('categoriesTableBody');
    
    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No categories found</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    categories.forEach(cat => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escapeHtml(cat.name)}</td>
            <td>${escapeHtml(cat.slug)}</td>
            <td>${cat.order_num}</td>
            <td class="table-actions">
                <button class="btn btn-secondary btn-sm" onclick="editCategory(${cat.id}, '${escapeHtml(cat.name)}', ${cat.order_num})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteCategory(${cat.id}, '${escapeHtml(cat.name)}')">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function showAddCategoryModal() {
    const name = prompt('Enter category name:');
    if (name) {
        addCategory(name, 0);
    }
}

async function addCategory(name, order_num) {
    const formData = new FormData();
    formData.append('action', 'add_category');
    formData.append('name', name);
    formData.append('order_num', order_num);
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            loadCategories();
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        showAlert('danger', 'Error adding category');
        console.error('Error:', error);
    }
}

async function editCategory(id, currentName, currentOrder) {
    const name = prompt('Enter category name:', currentName);
    if (name && name !== currentName) {
        const formData = new FormData();
        formData.append('action', 'update_category');
        formData.append('id', id);
        formData.append('name', name);
        formData.append('order_num', currentOrder);
        
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert('success', data.message);
                loadCategories();
            } else {
                showAlert('danger', data.message);
            }
        } catch (error) {
            showAlert('danger', 'Error updating category');
            console.error('Error:', error);
        }
    }
}

async function deleteCategory(id, name) {
    if (!confirm(`Are you sure you want to delete category "${name}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_category');
    formData.append('id', id);
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            loadCategories();
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        showAlert('danger', 'Error deleting category');
        console.error('Error:', error);
    }
}

// Load Categories for Select
async function loadCategoriesForSelect(selectId, selectedId = null) {
    try {
        const response = await fetch('api.php?action=list_categories');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById(selectId);
            data.data.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                if (selectedId && cat.id == selectedId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Settings Form
const settingsForm = document.getElementById('settingsForm');
settingsForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(settingsForm);
    const settings = {};
    
    for (let [key, value] of formData.entries()) {
        settings[key] = value;
    }
    
    const data = new FormData();
    data.append('action', 'save_settings');
    data.append('settings', JSON.stringify(settings));
    
    Object.keys(settings).forEach(key => {
        data.append(`settings[${key}]`, settings[key]);
    });
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: data
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        showAlert('danger', 'Error saving settings');
        console.error('Error:', error);
    }
});

// TMDB Search
async function searchTMDB() {
    const query = document.getElementById('tmdbSearch').value;
    const type = document.getElementById('tmdbType').value;
    
    if (!query) {
        showAlert('warning', 'Please enter a search term');
        return;
    }
    
    const resultsDiv = document.getElementById('tmdbResults');
    resultsDiv.innerHTML = '<div style="text-align: center; padding: 20px;"><div class="loading-spinner"></div></div>';
    
    try {
        const response = await fetch(`api.php?action=tmdb_search&query=${encodeURIComponent(query)}&type=${type}`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            renderTMDBResults(data.data, type);
        } else {
            resultsDiv.innerHTML = '<div class="alert alert-info">No results found</div>';
        }
    } catch (error) {
        resultsDiv.innerHTML = '<div class="alert alert-danger">Error searching TMDB</div>';
        console.error('Error:', error);
    }
}

function renderTMDBResults(results, type) {
    const resultsDiv = document.getElementById('tmdbResults');
    
    let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 20px;">';
    
    results.forEach(item => {
        const title = type === 'movie' ? item.title : item.name;
        const date = type === 'movie' ? item.release_date : item.first_air_date;
        const year = date ? date.split('-')[0] : '';
        const posterUrl = item.poster_path ? `https://image.tmdb.org/t/p/w200${item.poster_path}` : '../assets/images/placeholder.jpg';
        
        html += `
            <div style="background: var(--surface-light); border-radius: 8px; overflow: hidden; cursor: pointer; transition: transform 0.3s;" onclick="importFromTMDB(${item.id}, '${type}')">
                <img src="${posterUrl}" alt="${escapeHtml(title)}" style="width: 100%; height: 225px; object-fit: cover;">
                <div style="padding: 10px;">
                    <h4 style="font-size: 0.9rem; margin-bottom: 5px;">${escapeHtml(title)}</h4>
                    <p style="font-size: 0.8rem; color: var(--text-secondary);">${year}</p>
                    <p style="font-size: 0.8rem; color: var(--accent);"><i class="fas fa-star"></i> ${item.vote_average || 'N/A'}</p>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    resultsDiv.innerHTML = html;
}

async function importFromTMDB(tmdb_id, type) {
    if (!confirm('Import this content?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'tmdb_import');
    formData.append('tmdb_id', tmdb_id);
    formData.append('type', type);
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message + ' You can now add video sources.');
            setTimeout(() => {
                editContent(data.id);
            }, 1000);
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        showAlert('danger', 'Error importing content');
        console.error('Error:', error);
    }
}

// Utility Functions
function showAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'}"></i> ${message}`;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.3s';
        setTimeout(() => alert.remove(), 300);
    }, 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
loadCategoriesForSelect('categorySelect');
