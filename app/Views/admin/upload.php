<?php 
$title = "Media Upload";
$bodyClass = "admin-page";
ob_start();
?>

<div class="page-header">
    <div class="container">
        <div class="header-content">
            <div class="header-info">
                <h1><i class="fas fa-upload"></i> Media Upload</h1>
                <p>Upload video and audio files for karaoke content</p>
            </div>
            <div class="header-actions">
                <a href="/admin/songs" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Songs
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <?php if (isset($uploadedFiles) || isset($errors)): ?>
            <!-- Upload Results -->
            <div class="upload-results">
                <?php if (!empty($errors ?? [])): ?>
                <div class="error-section">
                    <h3><i class="fas fa-exclamation-triangle"></i> Upload Errors</h3>
                    <div class="error-list">
                        <?php foreach ($errors as $error): ?>
                        <div class="error-item">
                            <i class="fas fa-times-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($uploadedFiles ?? [])): ?>
                <div class="success-section">
                    <h3><i class="fas fa-check-circle"></i> Uploaded Files</h3>
                    <div class="uploaded-files">
                        <?php foreach ($uploadedFiles as $file): ?>
                        <div class="file-item">
                            <div class="file-info">
                                <div class="file-icon">
                                    <i class="fas fa-<?= $file['type'] === 'video' ? 'file-video' : 'file-audio' ?>"></i>
                                </div>
                                <div class="file-details">
                                    <h4><?= htmlspecialchars($file['original_name']) ?></h4>
                                    <p>Saved as: <?= htmlspecialchars($file['stored_name']) ?></p>
                                    <div class="file-meta">
                                        <span class="file-size">
                                            <i class="fas fa-hdd"></i>
                                            <?= formatFileSize($file['size']) ?>
                                        </span>
                                        <span class="file-type">
                                            <i class="fas fa-tag"></i>
                                            <?= strtoupper($file['type']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="file-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="copyPath('<?= htmlspecialchars($file['path']) ?>')">
                                    <i class="fas fa-copy"></i>
                                    Copy Path
                                </button>
                                <a href="<?= htmlspecialchars($file['path']) ?>" 
                                   target="_blank" 
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-eye"></i>
                                    View
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Upload Form -->
        <div class="upload-container">
            <div class="upload-card">
                <div class="card-header">
                    <h2>Upload Media Files</h2>
                    <p>Supported formats: MP4, AVI, MOV, WMV, FLV, WebM, MKV (videos) | MP3, WAV, FLAC, AAC, OGG (audio)</p>
                </div>
                <div class="card-content">
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <?= csrf_field() ?>
                        
                        <div class="form-group">
                            <label for="media_files">Select Files</label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <div class="upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <h3>Drag & Drop Files Here</h3>
                                    <p>or click to browse</p>
                                    <input type="file" 
                                           id="media_files" 
                                           name="media_files[]" 
                                           multiple 
                                           accept="video/*,audio/*"
                                           required>
                                </div>
                            </div>
                            <small class="form-help">
                                Maximum file size: <?= formatFileSize(100 * 1024 * 1024) ?> per file
                            </small>
                        </div>
                        
                        <div class="selected-files" id="selectedFiles">
                            <!-- Selected files will be displayed here -->
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                                <i class="fas fa-times"></i>
                                Clear Selection
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i>
                                Upload Files
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="info-card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Upload Guidelines</h3>
                </div>
                <div class="card-content">
                    <div class="guideline-list">
                        <div class="guideline-item">
                            <i class="fas fa-video"></i>
                            <div>
                                <strong>Video Formats</strong>
                                <p>MP4, AVI, MOV, WMV, FLV, WebM, MKV</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <i class="fas fa-music"></i>
                            <div>
                                <strong>Audio Formats</strong>
                                <p>MP3, WAV, FLAC, AAC, OGG</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <i class="fas fa-hdd"></i>
                            <div>
                                <strong>File Size Limit</strong>
                                <p>Maximum <?= formatFileSize(100 * 1024 * 1024) ?> per file</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <i class="fas fa-magic"></i>
                            <div>
                                <strong>Quality Tips</strong>
                                <p>Higher quality files provide better karaoke experience</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.upload-container {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
}

.upload-card {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.card-header h2 {
    margin: 0 0 0.5rem 0;
    color: var(--gray-800);
}

.card-header p {
    margin: 0;
    color: var(--gray-600);
    font-size: 0.875rem;
}

.card-content {
    padding: 1.5rem;
}

.file-upload-area {
    border: 2px dashed var(--gray-300);
    border-radius: 0.5rem;
    padding: 3rem 1rem;
    text-align: center;
    transition: var(--transition);
    cursor: pointer;
    position: relative;
}

.file-upload-area:hover {
    border-color: var(--primary-color);
    background: var(--primary-light);
}

.file-upload-area.dragover {
    border-color: var(--primary-color);
    background: var(--primary-light);
}

.upload-placeholder i {
    font-size: 3rem;
    color: var(--gray-400);
    margin-bottom: 1rem;
}

.upload-placeholder h3 {
    margin: 0 0 0.5rem 0;
    color: var(--gray-700);
}

.upload-placeholder p {
    margin: 0;
    color: var(--gray-500);
}

.upload-placeholder input[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.selected-files {
    margin-top: 1rem;
}

.file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
}

.file-item:last-child {
    margin-bottom: 0;
}

.file-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.file-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-details h4 {
    margin: 0;
    font-size: 0.875rem;
    color: var(--gray-800);
}

.file-details p {
    margin: 0;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.file-meta {
    display: flex;
    gap: 1rem;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--gray-500);
}

.file-actions {
    display: flex;
    gap: 0.5rem;
}

.upload-results {
    margin-bottom: 2rem;
}

.error-section,
.success-section {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.error-section h3,
.success-section h3 {
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.error-section h3 {
    color: var(--error-color);
}

.success-section h3 {
    color: var(--success-color);
}

.error-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.error-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--error-color);
    font-size: 0.875rem;
}

.uploaded-files {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.guideline-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.guideline-item {
    display: flex;
    gap: 0.75rem;
}

.guideline-item i {
    color: var(--primary-color);
    font-size: 1.25rem;
    margin-top: 0.25rem;
    flex-shrink: 0;
}

.guideline-item strong {
    display: block;
    margin-bottom: 0.25rem;
    color: var(--gray-800);
}

.guideline-item p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--gray-600);
}

@media (max-width: 1024px) {
    .upload-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// File upload handling
const fileInput = document.getElementById('media_files');
const uploadArea = document.getElementById('fileUploadArea');
const selectedFilesContainer = document.getElementById('selectedFiles');

fileInput.addEventListener('change', function() {
    displaySelectedFiles(this.files);
});

uploadArea.addEventListener('click', function() {
    fileInput.click();
});

uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
});

uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    fileInput.files = files;
    displaySelectedFiles(files);
});

function displaySelectedFiles(files) {
    if (files.length === 0) {
        selectedFilesContainer.innerHTML = '';
        return;
    }
    
    const html = Array.from(files).map(file => `
        <div class="file-item">
            <div class="file-info">
                <div class="file-icon">
                    <i class="fas fa-${file.type.startsWith('video/') ? 'file-video' : 'file-audio'}"></i>
                </div>
                <div class="file-details">
                    <h4>${escapeHtml(file.name)}</h4>
                    <p>${formatFileSize(file.size)} - ${file.type}</p>
                </div>
            </div>
            <div class="file-actions">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile('${file.name}')">
                    <i class="fas fa-times"></i>
                    Remove
                </button>
            </div>
        </div>
    `).join('');
    
    selectedFilesContainer.innerHTML = html;
}

function removeFile(fileName) {
    const dt = new DataTransfer();
    const files = fileInput.files;
    
    for (let i = 0; i < files.length; i++) {
        if (files[i].name !== fileName) {
            dt.items.add(files[i]);
        }
    }
    
    fileInput.files = dt.files;
    displaySelectedFiles(fileInput.files);
}

function clearSelection() {
    fileInput.value = '';
    selectedFilesContainer.innerHTML = '';
}

function copyPath(path) {
    navigator.clipboard.writeText(path).then(() => {
        showToast('File path copied to clipboard!');
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php 
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>