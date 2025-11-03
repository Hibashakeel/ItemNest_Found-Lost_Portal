<?php
require_once "../config.php";
require_once "../classes/Item.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php"); exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item = new Item();
    $ok = $item->addItem(
        $_SESSION['user']['id'],
        $_POST['item_name'],
        $_POST['category'],
        $_POST['location'],
        $_POST['date_found'],
        $_POST['description'],
        $_FILES['image'],
        'found'
    );
    $message = $ok ? "Found item reported successfully!" : "Error reporting item.";
}

include "../includes/header.php";
?>

<div class="report-form-container">
  <!-- Report Header -->
  <div class="report-header found-report">
    <h2>✅ Report Found Item</h2>
    <p>Help reunite someone with their lost item by providing details of what you found. Your help could make someone's day!</p>
  </div>

  <!-- Form Steps -->
  <div class="form-steps">
    <div class="step active">
      <div class="step-number">1</div>
      <div class="step-label">Item Details</div>
    </div>
    <div class="step">
      <div class="step-number">2</div>
      <div class="step-label">Location & Time</div>
    </div>
    <div class="step">
      <div class="step-number">3</div>
      <div class="step-label">Review & Submit</div>
    </div>
  </div>

  <!-- Success Message -->
  <?php if($message && strpos($message, 'successfully') !== false): ?>
    <div class="form-success">
      <div class="success-icon">🎉</div>
      <h3>Thank You!</h3>
      <p>Your found item has been reported successfully. We'll notify the owner if they've reported it as lost.</p>
      <a href="dashboard.php" class="btn">View Dashboard</a>
    </div>
  <?php endif; ?>

  <!-- Report Form -->
  <form method="POST" enctype="multipart/form-data" class="report-form found-form">
    
    <!-- Error Message -->
    <?php if($message && strpos($message, 'Error') !== false): ?>
      <div class="alert alert-error">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <!-- Basic Information Section -->
    <div class="form-section">
      <div class="section-header">
        <div class="section-icon">📝</div>
        <div>
          <h3 class="section-title">Basic Information</h3>
          <p class="section-description">Tell us about the item you found</p>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">
          <span>Item Name</span>
          <span class="required">*</span>
        </label>
        <input type="text" name="item_name" class="form-control" placeholder="e.g., iPhone 13, Wallet, Keys..." required>
        <div class="form-hint">Be specific to help with matching</div>
      </div>

      <div class="form-group">
        <label class="form-label">
          <span>Category</span>
          <span class="required">*</span>
        </label>
        <select name="category" class="form-control" required>
          <option value="">Select a category</option>
          <option value="electronics">📱 Electronics</option>
          <option value="clothing">👕 Clothing</option>
          <option value="accessories">👜 Accessories</option>
          <option value="documents">📄 Documents</option>
          <option value="keys">🔑 Keys</option>
          <option value="jewelry">💍 Jewelry</option>
          <option value="bags">🎒 Bags & Backpacks</option>
          <option value="books">📚 Books & Stationery</option>
          <option value="sports">⚽ Sports Equipment</option>
          <option value="other">📦 Other</option>
        </select>
      </div>
    </div>

    <!-- Location & Time Section -->
    <div class="form-section">
      <div class="section-header">
        <div class="section-icon">📍</div>
        <div>
          <h3 class="section-title">Location & Time</h3>
          <p class="section-description">Where and when did you find the item?</p>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">
          <span>Location Found</span>
          <span class="required">*</span>
        </label>
        <input type="text" name="location" class="form-control" 
               placeholder="e.g., Central Park, Main Building Room 201, Shopping Mall..." required>
        <div class="form-hint">Be as specific as possible about the location</div>
      </div>

      <div class="form-group">
        <label class="form-label">
          <span>Date Found</span>
          <span class="required">*</span>
        </label>
        <input type="date" name="date_found" class="form-control" required>
      </div>
    </div>

    <!-- Description & Image Section -->
    <div class="form-section">
      <div class="section-header">
        <div class="section-icon">📋</div>
        <div>
          <h3 class="section-title">Description & Image</h3>
          <p class="section-description">Help identify the item with details and photos</p>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">
          <span>Description</span>
          <span class="required">*</span>
        </label>
        <textarea name="description" class="form-control" 
                  placeholder="Describe the item in detail (color, brand, unique features, contents, condition...)" 
                  rows="5" required></textarea>
        <div class="char-counter"><span id="charCount">0</span>/500 characters</div>
      </div>

      <div class="form-group">
        <label class="form-label">
          <span>Item Image</span>
        </label>
        <div class="file-upload-container">
          <label class="file-upload-label" id="fileUploadLabel">
            <div class="file-upload-icon">📸</div>
            <div class="file-upload-text">Click to upload image</div>
            <div class="file-upload-hint">JPG, PNG, GIF, WEBP (Max 5MB)</div>
            <input type="file" name="image" class="file-input" accept="image/*" onchange="previewImage(event)">
          </label>
        </div>
        <div class="image-preview-container">
          <img id="preview" src="#" alt="Preview" class="image-preview" style="display:none;">
          <div class="preview-actions" id="previewActions" style="display:none;">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeImage()">Remove Image</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Help Text -->
    <div class="help-text">
      <h4>💡 Tips for Better Matching</h4>
      <p>• Provide clear details about the item's appearance<br>
         • Mention any identifiable features or contents<br>
         • Upload clear photos from different angles<br>
         • Be precise about where and when you found it</p>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
      <a href="dashboard.php" class="btn-secondary">Cancel</a>
      <button type="submit" class="btn">
        ✅ Report Found Item
      </button>
    </div>
  </form>
</div>

<script>
function previewImage(event) {
  const reader = new FileReader();
  const file = event.target.files[0];
  
  if (file) {
    // Update file upload label
    document.getElementById('fileUploadLabel').classList.add('has-file');
    
    // Show preview
    reader.onload = function(){
      const output = document.getElementById('preview');
      output.src = reader.result;
      output.style.display = 'block';
      document.getElementById('previewActions').style.display = 'flex';
    };
    reader.readAsDataURL(file);
  }
}

function removeImage() {
  const fileInput = document.querySelector('input[type="file"]');
  fileInput.value = '';
  document.getElementById('preview').style.display = 'none';
  document.getElementById('previewActions').style.display = 'none';
  document.getElementById('fileUploadLabel').classList.remove('has-file');
}

// Character counter for description
document.querySelector('textarea[name="description"]').addEventListener('input', function(e) {
  const charCount = e.target.value.length;
  document.getElementById('charCount').textContent = charCount;
  
  const counter = document.querySelector('.char-counter');
  if (charCount > 450) {
    counter.classList.add('warning');
  } else {
    counter.classList.remove('warning');
  }
  
  if (charCount > 500) {
    counter.classList.add('error');
  } else {
    counter.classList.remove('error');
  }
});
</script>

<?php include "../includes/footer.php"; ?>