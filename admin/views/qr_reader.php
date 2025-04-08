<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner</title>
    <!-- Bootstrap 5 CSS -->
   
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .scanner-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            aspect-ratio: 1;
            border: 2px solid #dee2e6;
            border-radius: 0.375rem;
            overflow: hidden;
            background-color: #f8f9fa;
        }
        #qr-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .scanner-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0,0,0,0.5);
            color: white;
            flex-direction: column;
        }
        .scanner-frame {
            position: absolute;
            border: 3px solid rgba(0, 255, 0, 0.5);
            border-radius: 0.5rem;
            box-shadow: 0 0 0 100vmax rgba(0, 0, 0, 0.7);
        }
        .result-container {
            min-height: 100px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
      
       <!-- Trigger Button -->
<!-- Trigger Button -->
<div class="">
<button type="button" class="btn btn-sm fw-medium rounded-3 py-3 px-3 text-light" style="background-color: #07D090;" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
    <i class="fas fa-qrcode me-1 text-light"></i> Scan QR Code
</button>



</div>


  


</div>

        <!-- QR Scanner Modal -->
        <div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="qrScannerModalLabel">QR Code Scanner</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs mb-4" id="qrScannerTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="scan-tab" data-bs-toggle="tab" data-bs-target="#scan-tab-pane" type="button" role="tab">
                                    <i class="fas fa-camera me-1"></i> Scan
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-tab-pane" type="button" role="tab">
                                    <i class="fas fa-upload me-1"></i> Upload
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="qrScannerTabsContent">
                            <!-- Scan Tab -->
                            <div class="tab-pane fade show active" id="scan-tab-pane" role="tabpanel">
                                <div class="scanner-container mb-3">
                                    <video id="qr-video" playsinline></video>
                                    <div class="scanner-frame"></div>
                                    <div class="scanner-overlay" id="scanner-overlay">
                                        <div class="spinner-border text-light mb-2" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mb-0">Initializing camera...</p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center gap-2 mb-3">
                                    <button class="btn btn-outline-primary" id="toggle-camera">
                                        <i class="fas fa-sync-alt me-1"></i> Switch Camera
                                    </button>
                                    <button class="btn btn-outline-secondary" id="stop-scan">
                                        <i class="fas fa-stop me-1"></i> Stop Scanner
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Upload Tab -->
                            <div class="tab-pane fade" id="upload-tab-pane" role="tabpanel">
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <label for="file-upload" class="btn btn-primary btn-lg">
                                            <i class="fas fa-folder-open me-2"></i> Select QR Code Image
                                        </label>
                                        <input type="file" id="file-upload" accept="image/*" class="d-none">
                                    </div>
                                    <p class="text-muted">or drag and drop image here</p>
                                    <div class="border rounded p-3 bg-light" id="drop-zone">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                        <p class="mb-0">Drag & drop your QR code image here</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Scan Results -->
                        <div class="card mt-3">
                            <div class="card-header  text-white" style="background-color: #FF902B;">
                                <i class="fas fa-info-circle me-1"></i> Scan Results
                            </div>
                            <div class="card-body result-container">
                                <div id="scan-result" class="text-center py-4 text-muted">
                                    No QR code scanned yet
                                </div>
                                <div class="d-grid">
                                    <button id="copy-result" class="btn btn-outline-secondary" disabled>
                                        <i class="fas fa-copy me-1"></i> Copy to Clipboard
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- QR Code Scanner Library -->
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal elements
            const modal = document.getElementById('qrScannerModal');
            const video = document.getElementById('qr-video');
            const scannerOverlay = document.getElementById('scanner-overlay');
            const scanResult = document.getElementById('scan-result');
            const copyButton = document.getElementById('copy-result');
            const toggleCameraBtn = document.getElementById('toggle-camera');
            const stopScanBtn = document.getElementById('stop-scan');
            const fileUpload = document.getElementById('file-upload');
            const dropZone = document.getElementById('drop-zone');
            
            let stream = null;
            let scanning = false;
            let currentFacingMode = "environment"; // Default to rear camera
            
            // Initialize scanner when modal opens
            modal.addEventListener('shown.bs.modal', startScanner);
            modal.addEventListener('hidden.bs.modal', stopScanner);
            
            // Camera toggle
            toggleCameraBtn.addEventListener('click', toggleCamera);
            
            // Stop scanner button
            stopScanBtn.addEventListener('click', stopScanner);
            
            // File upload handling
            fileUpload.addEventListener('change', handleFileUpload);
            
            // Drag and drop handling
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-primary');
            });
            
            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-primary');
            });
            
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-primary');
                if (e.dataTransfer.files.length) {
                    fileUpload.files = e.dataTransfer.files;
                    handleFileUpload();
                }
            });
            
            // Copy result button
            copyButton.addEventListener('click', () => {
                navigator.clipboard.writeText(scanResult.textContent)
                    .then(() => {
                        const originalText = copyButton.innerHTML;
                        copyButton.innerHTML = '<i class="fas fa-check me-1"></i> Copied!';
                        setTimeout(() => {
                            copyButton.innerHTML = originalText;
                        }, 2000);
                    });
            });
            
            async function startScanner() {
                scanning = true;
                scannerOverlay.style.display = 'flex';
                
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { 
                            facingMode: currentFacingMode,
                            width: { ideal: 1280 },
                            height: { ideal: 1280 }
                        },
                        audio: false
                    });
                    
                    video.srcObject = stream;
                    video.play();
                    
                    video.onplaying = () => {
                        scannerOverlay.style.display = 'none';
                        scanFrame();
                    };
                } catch (err) {
                    console.error("Error accessing camera:", err);
                    scannerOverlay.innerHTML = `
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="text-center">Camera access denied<br><small class="text-muted">${err.message}</small></p>
                    `;
                }
            }
            
            function stopScanner() {
                scanning = false;
                scannerOverlay.style.display = 'flex';
                scannerOverlay.innerHTML = `
                    <div class="spinner-border text-light mb-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mb-0">Camera stopped</p>
                `;
                
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    video.srcObject = null;
                }
            }
            
            async function toggleCamera() {
                currentFacingMode = currentFacingMode === "user" ? "environment" : "user";
                stopScanner();
                await new Promise(resolve => setTimeout(resolve, 500));
                startScanner();
            }
            
            function scanFrame() {
                if (!scanning) return;
                
                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height);
                    
                    if (code) {
                        displayResult(code.data);
                        // Draw rectangle around QR code
                        drawDetectionRect(code.location);
                    } else {
                        clearDetectionRect();
                    }
                }
                
                requestAnimationFrame(scanFrame);
            }
            
            function drawDetectionRect(location) {
                const scannerFrame = document.querySelector('.scanner-frame');
                scannerFrame.style.width = `${location.right - location.left}px`;
                scannerFrame.style.height = `${location.bottom - location.top}px`;
                scannerFrame.style.left = `${location.left}px`;
                scannerFrame.style.top = `${location.top}px`;
            }
            
            function clearDetectionRect() {
                const scannerFrame = document.querySelector('.scanner-frame');
                scannerFrame.style.width = '0';
                scannerFrame.style.height = '0';
            }
            
            function handleFileUpload() {
                if (!fileUpload.files.length) return;
                
                const file = fileUpload.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0);
                        
                        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height);
                        
                        if (code) {
                            displayResult(code.data);
                        } else {
                            displayResult("No QR code found in the image", true);
                        }
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
            
            function displayResult(result, isError = false) {
                scanResult.textContent = result;
                scanResult.className = isError ? 'text-danger' : 'text-success';
                copyButton.disabled = isError;
                
                // Switch to results tab if not already there
                const scanTab = new bootstrap.Tab(document.getElementById('scan-tab'));
                const uploadTab = new bootstrap.Tab(document.getElementById('upload-tab'));
                uploadTab.show();
            }
        });
    </script>
</body>
</html>