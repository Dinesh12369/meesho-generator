// ============================================================================
// IMAGE GENERATOR - JAVASCRIPT
// ============================================================================

// Global Variables
let selectedCategories = [];
let selectedCategoryData = [];
let selectedImage = null;
let debounceTimer;

// ============================================================================
// INITIALIZATION
// ============================================================================
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    initializeEventListeners();
    updateStats();
});

function initializeEventListeners() {
    // Image upload
    const uploadBtn = document.getElementById('uploadBtn');
    const imageFile = document.getElementById('imageFile');
    const removeImageBtn = document.getElementById('removeImageBtn');
    
    if (uploadBtn) {
        uploadBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (imageFile) {
                imageFile.click();
            }
        });
    }
    
    if (imageFile) {
        imageFile.addEventListener('change', handleImageSelect);
    }
    
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', removeImage);
    }
    
    // Category dropdown
    const categoryDropdownBtn = document.getElementById('categoryDropdownBtn');
    const categorySearch = document.getElementById('categorySearch');
    
    if (categoryDropdownBtn) {
        categoryDropdownBtn.addEventListener('click', toggleDropdown);
    }
    
    if (categorySearch) {
        categorySearch.addEventListener('input', searchCategories);
    }
    
    // Stats update
    const numVariants = document.getElementById('numVariants');
    const targetSize = document.getElementById('targetSize');
    
    if (numVariants) {
        numVariants.addEventListener('input', updateStats);
    }
    
    if (targetSize) {
        targetSize.addEventListener('input', updateTargetSize);
    }
    
    // Form submission
    const generateForm = document.getElementById('generateForm');
    if (generateForm) {
        generateForm.addEventListener('submit', handleFormSubmit);
    }
    
    // Close dropdown on outside click
    document.addEventListener('click', handleOutsideClick);
}

// ============================================================================
// UI HELPER FUNCTIONS
// ============================================================================
function updateTargetSize() {
    const value = document.getElementById('targetSize').value;
    document.getElementById('targetSizeDisplay').textContent = value;
    updateStats();
}

function updateStats() {
    const numVariants = parseInt(document.getElementById('numVariants').value) || 50;
    const targetSize = parseInt(document.getElementById('targetSize').value) || 450;
    
    document.getElementById('totalVariantsFooter').textContent = numVariants;
    document.getElementById('statsVariants').textContent = numVariants;
    document.getElementById('statsSize').textContent = `~${targetSize} KB`;
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 p-4 rounded-xl shadow-2xl flex items-center gap-3 z-50 animate-slide-up border-2`;
    
    if (type === 'success') {
        toast.className += ' bg-green-500 text-white border-green-400';
        toast.innerHTML = `<i data-lucide="check-circle" class="h-5 w-5"></i><span class="font-semibold">${message}</span>`;
    } else if (type === 'error') {
        toast.className += ' bg-red-500 text-white border-red-400';
        toast.innerHTML = `<i data-lucide="x-circle" class="h-5 w-5"></i><span class="font-semibold">${message}</span>`;
    } else {
        toast.className += ' bg-blue-500 text-white border-blue-400';
        toast.innerHTML = `<i data-lucide="info" class="h-5 w-5"></i><span class="font-semibold">${message}</span>`;
    }
    
    document.body.appendChild(toast);
    lucide.createIcons();
    setTimeout(() => toast.remove(), 3000);
}

// ============================================================================
// CATEGORY MANAGEMENT
// ============================================================================
function toggleDropdown() {
    const dropdown = document.getElementById('categoryDropdown');
    dropdown.classList.toggle('hidden');
    
    if (!dropdown.classList.contains('hidden')) {
        document.getElementById('categorySearch').focus();
    }
    
    lucide.createIcons();
}

function handleOutsideClick(event) {
    const dropdown = document.getElementById('categoryDropdown');
    const button = event.target.closest('#categoryDropdownBtn');
    const dropdownElement = event.target.closest('#categoryDropdown');
    
    if (!button && !dropdownElement && !dropdown.classList.contains('hidden')) {
        dropdown.classList.add('hidden');
    }
}

function searchCategories() {
    const query = document.getElementById('categorySearch').value.trim();
    
    clearTimeout(debounceTimer);
    
    debounceTimer = setTimeout(async () => {
        if (query.length < 2) {
            document.getElementById('categoryList').innerHTML = `
                <div class="px-4 py-8 text-sm text-gray-500 text-center">
                    <i data-lucide="search" class="h-8 w-8 mx-auto mb-3 text-gray-300"></i>
                    <p class="font-medium">Type at least 2 characters to search...</p>
                    <p class="text-xs mt-2 text-gray-400">Try: "saree", "dress", "kurti", etc.</p>
                </div>
            `;
            lucide.createIcons();
            return;
        }
        
        // Show loading state
        document.getElementById('categoryList').innerHTML = `
            <div class="px-4 py-8 text-sm text-gray-500 text-center">
                <i data-lucide="loader" class="h-8 w-8 mx-auto mb-3 text-purple-600 animate-spin"></i>
                <p class="font-medium">Searching categories...</p>
            </div>
        `;
        lucide.createIcons();
        
        try {
            // Use ONLY real Meesho API
            const response = await fetch('api_search_categories.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    query: query,
                    offset: 0,
                    size: 50,
                    bulk_upload_enabled: false,
                    supplier_enabled: true
                })
            });
            
            let data;
            try {
                const responseText = await response.text();
                data = JSON.parse(responseText);
            } catch (jsonError) {
                throw new Error('Invalid response from server. Please refresh login.');
            }
            
            if (!data.success) {
                if (data.requires_login) {
                    document.getElementById('categoryList').innerHTML = `
                        <div class="px-4 py-8 text-sm text-center">
                            <i data-lucide="lock" class="h-8 w-8 mx-auto mb-3 text-yellow-600"></i>
                            <p class="font-medium text-yellow-600 mb-2">Session Expired</p>
                            <p class="text-xs text-gray-600 mb-3">Please refresh your Meesho login</p>
                            <a href="api_auto_login.php" target="_blank" class="inline-block px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 text-sm font-semibold">
                                Refresh Login
                            </a>
                        </div>
                    `;
                    lucide.createIcons();
                    return;
                }
                throw new Error(data.error || 'Failed to fetch categories');
            }
            
            const categories = data.categories || [];
            
            if (categories.length === 0) {
                document.getElementById('categoryList').innerHTML = `
                    <div class="px-4 py-8 text-sm text-gray-500 text-center">
                        <i data-lucide="inbox" class="h-8 w-8 mx-auto mb-3 text-gray-300"></i>
                        <p class="font-medium mb-2">No categories found for "${query}"</p>
                        <p class="text-xs text-gray-400">Try: "saree", "kurti", "dress", "shirt"</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }
            
            renderCategories(categories);
            
        } catch (error) {
            console.error('Category search error:', error);
            document.getElementById('categoryList').innerHTML = `
                <div class="px-4 py-8 text-sm text-red-500 text-center">
                    <i data-lucide="alert-circle" class="h-8 w-8 mx-auto mb-3"></i>
                    <p class="font-medium mb-2">Connection Error</p>
                    <p class="text-xs mb-3">${error.message}</p>
                    <div class="flex gap-2 justify-center mt-3">
                        <button onclick="document.getElementById('categorySearch').dispatchEvent(new Event('input'))" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm font-semibold">
                            Try Again
                        </button>
                        <a href="api_auto_login.php" target="_blank" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 text-sm font-semibold">
                            Refresh Login
                        </a>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }
    }, 500);
}

function renderCategories(categories) {
    const categoryList = document.getElementById('categoryList');
    
    if (categories.length === 0) {
        categoryList.innerHTML = `
            <div class="px-4 py-8 text-sm text-gray-500 text-center">
                <i data-lucide="inbox" class="h-8 w-8 mx-auto mb-3 text-gray-300"></i>
                <p class="font-medium">No categories found</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    // Group by category type
    const grouped = { 
        'sub-sub-category': [], 
        'sub-category': [], 
        'category': [],
        'other': []
    };
    
    categories.forEach(cat => {
        const type = cat.category_type || 'other';
        if (grouped[type]) {
            grouped[type].push(cat);
        } else {
            grouped['other'].push(cat);
        }
    });
    
    let html = '';
    
    // Render sub-sub-categories (most specific)
    if (grouped['sub-sub-category'].length > 0) {
        html += '<div class="px-4 py-2 text-xs font-bold text-purple-600 bg-purple-50 sticky top-[60px] z-10">📌 SPECIFIC CATEGORIES</div>';
        html += grouped['sub-sub-category'].map(createCategoryCheckbox).join('');
    }
    
    // Render sub-categories (broad)
    if (grouped['sub-category'].length > 0) {
        html += '<div class="px-4 py-2 text-xs font-bold text-blue-600 bg-blue-50 sticky top-[60px] z-10">📂 BROAD CATEGORIES</div>';
        html += grouped['sub-category'].map(createCategoryCheckbox).join('');
    }
    
    // Render main categories
    if (grouped['category'].length > 0) {
        html += '<div class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-50 sticky top-[60px] z-10">🏠 MAIN CATEGORIES</div>';
        html += grouped['category'].map(createCategoryCheckbox).join('');
    }
    
    // Render other categories
    if (grouped['other'].length > 0) {
        html += grouped['other'].map(createCategoryCheckbox).join('');
    }
    
    categoryList.innerHTML = html;
    lucide.createIcons();
}

function createCategoryCheckbox(category) {
    const isSelected = selectedCategoryData.some(cat => cat.id === category.id);
    const isDisabled = selectedCategories.length >= 5 && !isSelected;
    
    return `
        <label class="flex items-start px-4 py-3 hover:bg-purple-50 cursor-pointer border-b border-gray-100 ${isDisabled ? 'opacity-50 cursor-not-allowed' : ''}">
            <input type="checkbox" name="categories" value="${category.id}" 
                data-category-name="${escapeHtml(category.name)}"
                data-category-chain="${escapeHtml(category.chain)}"
                onchange="updateSelectedCategories()" 
                ${isSelected ? 'checked' : ''}
                ${isDisabled ? 'disabled' : ''}
                class="w-5 h-5 text-purple-600 border-2 border-gray-300 rounded mt-0.5">
            <div class="ml-3 flex-1">
                <div class="text-sm font-semibold text-gray-900">${escapeHtml(category.name)}</div>
                <div class="text-xs text-gray-500 mt-0.5">${escapeHtml(category.chain)}</div>
            </div>
        </label>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateSelectedCategories() {
    const checkboxes = document.querySelectorAll('input[name="categories"]:checked');
    selectedCategories = [];
    selectedCategoryData = [];
    
    checkboxes.forEach(cb => {
        selectedCategoryData.push({
            id: cb.value,
            name: cb.dataset.categoryName,
            chain: cb.dataset.categoryChain
        });
        selectedCategories.push(cb.dataset.categoryName);
    });
    
    if (selectedCategories.length > 5) {
        showToast('Maximum 5 categories allowed', 'error');
        checkboxes[checkboxes.length - 1].checked = false;
        selectedCategories.pop();
        selectedCategoryData.pop();
    }
    
    document.getElementById('selectedCategoryIds').value = JSON.stringify(
        selectedCategoryData.map(cat => cat.id)
    );
    
    const display = document.getElementById('selectedCategoriesDisplay');
    const list = document.getElementById('selectedCategoriesList');
    
    if (selectedCategories.length === 0) {
        display.innerHTML = '<span class="text-gray-400">Select categories...</span>';
        list.innerHTML = '';
    } else {
        display.innerHTML = selectedCategoryData.map(cat => `
            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-purple-600 text-white">
                ${escapeHtml(cat.name)}
            </span>
        `).join('');
        
        list.innerHTML = selectedCategoryData.map(cat => `
            <span class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-100 to-blue-100 border-2 border-purple-300 text-gray-900 text-sm px-4 py-2 rounded-xl">
                <div class="flex-1">
                    <div class="font-bold">${escapeHtml(cat.name)}</div>
                    <div class="text-xs text-gray-600">${escapeHtml(cat.chain)}</div>
                </div>
                <button type="button" onclick="removeCategory('${cat.id}')" class="hover:bg-red-100 rounded-lg p-1.5">
                    <i data-lucide="x" class="h-4 w-4 text-gray-600"></i>
                </button>
            </span>
        `).join('');
    }
    
    lucide.createIcons();
}

function removeCategory(categoryId) {
    selectedCategoryData = selectedCategoryData.filter(cat => cat.id != categoryId);
    selectedCategories = selectedCategoryData.map(cat => cat.name);
    
    const checkbox = document.querySelector(`input[name="categories"][value="${categoryId}"]`);
    if (checkbox) checkbox.checked = false;
    
    updateSelectedCategories();
}

// ============================================================================
// IMAGE HANDLING
// ============================================================================
function handleImageSelect(event) {
    const file = event.target.files[0];
    if (file && file.type.startsWith('image/')) {
        selectedImage = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imageName').textContent = file.name;
            document.getElementById('imageSize').textContent = (file.size / 1024).toFixed(2) + ' KB';
            document.getElementById('imagePreview').classList.remove('hidden');
            lucide.createIcons();
        };
        reader.readAsDataURL(file);
    } else {
        showToast('Please select a valid image file', 'error');
    }
}

function removeImage() {
    selectedImage = null;
    document.getElementById('imageFile').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
}

function downloadGeneration(generationId) {
    window.location.href = `download.php?id=${generationId}`;
}

// ============================================================================
// FORM SUBMISSION
// ============================================================================
async function handleFormSubmit(e) {
    e.preventDefault();
    
    if (selectedCategories.length === 0) {
        showToast('Please select at least one category', 'error');
        return;
    }
    
    if (!selectedImage) {
        showToast('Please upload an image', 'error');
        return;
    }
    
    const submitBtn = document.getElementById('generateBtn');
    const originalHTML = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i data-lucide="loader" class="h-6 w-6 animate-spin"></i> Generating...';
    submitBtn.disabled = true;
    lucide.createIcons();
    
    const numVariants = parseInt(document.getElementById('numVariants').value);
    showProgressModal(numVariants);
    
    const formData = new FormData();
    formData.append('image', selectedImage);
    formData.append('category_ids', JSON.stringify(selectedCategoryData.map(c => c.id)));
    formData.append('category_names', JSON.stringify(selectedCategoryData.map(c => c.name)));
    formData.append('num_variants', numVariants);
    formData.append('target_size_kb', document.getElementById('targetSize').value);
    formData.append('add_discount_tag', document.getElementById('addDiscountTag').checked);
    
    try {
        const response = await fetch('api_generate.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateProgress(numVariants, numVariants, 'Generation completed!', 
                         `All ${numVariants} variants created successfully`);
            
            // Update credits display
            if (data.credits_remaining !== undefined) {
                document.getElementById('creditsDisplay').textContent = data.credits_remaining;
                document.getElementById('statsCredits').textContent = data.credits_remaining;
            }
            
            showToast(`✅ Generated ${numVariants} images!`, 'success');
            
            setTimeout(() => {
                hideProgressModal();
                window.location.href = `viewer.php?id=${data.generation_id}`;
            }, 2000);
        } else {
            throw new Error(data.error || 'Generation failed');
        }
        
    } catch (error) {
        hideProgressModal();
        showToast(error.message || 'Network error', 'error');
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
        lucide.createIcons();
    }
}

// ============================================================================
// PROGRESS MODAL
// ============================================================================
function showProgressModal(totalImages) {
    document.getElementById('progressModal').classList.remove('hidden');
    document.getElementById('totalImages').textContent = totalImages;
    document.getElementById('imagesGenerated').textContent = '0';
    document.getElementById('imagesProgressBar').style.width = '0%';
    document.getElementById('currentStatus').textContent = 'Starting generation...';
    document.getElementById('currentSubStatus').textContent = 'Preparing images...';
    lucide.createIcons();
    
    // Simulate progress
    let current = 0;
    const interval = setInterval(() => {
        current += Math.floor(totalImages / 20);
        if (current > totalImages) current = totalImages;
        
        updateProgress(current, totalImages, 'Generating images...', 
                      `Processing variant ${current}/${totalImages}`);
        
        if (current >= totalImages) {
            clearInterval(interval);
        }
    }, 500);
}

function hideProgressModal() {
    document.getElementById('progressModal').classList.add('hidden');
}

function updateProgress(current, total, status, subStatus = '') {
    document.getElementById('imagesGenerated').textContent = current;
    const percentage = Math.round((current / total) * 100);
    document.getElementById('imagesProgressBar').style.width = percentage + '%';
    document.getElementById('currentStatus').textContent = status;
    document.getElementById('currentSubStatus').textContent = subStatus;
    lucide.createIcons();
}
