package com.loyalte.app.presentation.screens.scan

import androidx.camera.core.ExperimentalGetImage
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.ImageProxy
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.BarcodeScannerOptions
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.common.InputImage

/**
 * CameraX ImageAnalysis.Analyzer that uses ML Kit Barcode Scanning.
 *
 * Why ML Kit over ZXing Android Embedded?
 *  - Google-supported, actively maintained, part of the ML Kit SDK.
 *  - Works seamlessly with CameraX (same Google ecosystem).
 *  - Faster on modern devices via hardware acceleration.
 *  - No additional Activity required — runs inline with Compose camera preview.
 *
 * [onBarcodeDetected] is invoked on the camera executor thread; make sure any
 * downstream work is dispatched safely (the ViewModel handles that via coroutines).
 */
class BarcodeAnalyzer(
    private val onBarcodeDetected: (String) -> Unit
) : ImageAnalysis.Analyzer {

    private val scanner = BarcodeScanning.getClient(
        BarcodeScannerOptions.Builder()
            .setBarcodeFormats(Barcode.FORMAT_QR_CODE)
            .build()
    )

    @ExperimentalGetImage
    override fun analyze(imageProxy: ImageProxy) {
        val mediaImage = imageProxy.image
        if (mediaImage == null) {
            imageProxy.close()
            return
        }

        val inputImage = InputImage.fromMediaImage(
            mediaImage,
            imageProxy.imageInfo.rotationDegrees
        )

        scanner.process(inputImage)
            .addOnSuccessListener { barcodes ->
                barcodes.firstOrNull()?.rawValue?.let { onBarcodeDetected(it) }
            }
            .addOnCompleteListener {
                // Always close the proxy to unblock the next frame
                imageProxy.close()
            }
    }
}
