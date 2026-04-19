# Add project specific ProGuard rules here.

# Keep Room entity classes
-keep class com.loyalte.app.data.local.entity.** { *; }

# Keep Hilt generated classes
-keep class dagger.hilt.** { *; }
-keep class javax.inject.** { *; }

# Keep ML Kit classes
-keep class com.google.mlkit.** { *; }

# Keep CameraX
-keep class androidx.camera.** { *; }
