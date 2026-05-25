/**
 * firebase-config.js
 *
 * Firebase client-side configuration for LensLink.
 *
 * ⚠️ IMPORTANT — Firebase API Key Security:
 * These keys are client-side only and are intentionally "public".
 * However, you MUST restrict them in the Firebase Console:
 *   1. Go to https://console.firebase.google.com/project/lenslink-43561/settings/general
 *   2. Under "API restrictions", limit key usage to your deployed domain only
 *      (e.g. https://lenslink.vercel.app, http://localhost:5500)
 * Without this, anyone can use your Firebase project with your keys.
 */

const FIREBASE_CONFIG = {
    apiKey:            "AIzaSyAZcQZK_9sWrz8L7XVU-qra_bNfY86dKXc",
    authDomain:        "lenslink-43561.firebaseapp.com",
    projectId:         "lenslink-43561",
    storageBucket:     "lenslink-43561.firebasestorage.app",
    messagingSenderId: "859298338221",
    appId:             "1:859298338221:web:8f6dcc69e7270bf4997d24",
    measurementId:     "G-R4VZBVRQYT"
};
