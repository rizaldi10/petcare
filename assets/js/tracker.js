// Live Tracking Auto-Polling for Grooming Services
function initLiveTracker(intervalMs = 8000) {
    console.log("PetCare Live Tracker active. Interval: " + intervalMs + "ms");
    setInterval(() => {
        // Polling page reload or status endpoint
        const trackContainer = document.getElementById('liveTrackContainer');
        if (trackContainer && document.visibilityState === 'visible') {
            window.location.reload();
        }
    }, intervalMs);
}
