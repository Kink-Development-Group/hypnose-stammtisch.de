<script lang="ts">
  import { onMount } from "svelte";
  import router, { push } from "svelte-spa-router";
  // Import pages
  import About from "./pages/About.svelte";
  import CodeOfConduct from "./pages/CodeOfConduct.svelte";
  import Contact from "./pages/Contact.svelte";
  import Events from "./pages/Events.svelte";
  import Home from "./pages/Home.svelte";
  import Imprint from "./pages/Imprint.svelte";
  import LearningResources from "./pages/LearningResources.svelte";
  import NotFound from "./pages/NotFound.svelte";
  import Privacy from "./pages/Privacy.svelte";
  import Resources from "./pages/Resources.svelte";
  import Faq from "./pages/ResourcesFaq.svelte";
  import SafetyGuide from "./pages/ResourcesSafetyGuide.svelte";
  import SubmitEvent from "./pages/SubmitEvent.svelte";
  // Import admin pages
  import AdminEventsGuarded from "./pages/admin/AdminEventsGuarded.svelte";
  import AdminLogin from "./pages/admin/AdminLogin.svelte";
  import AdminMessagesGuarded from "./pages/admin/AdminMessagesGuarded.svelte";
  import AdminProfileGuarded from "./pages/admin/AdminProfileGuarded.svelte";
  import AdminUsersGuarded from "./pages/admin/AdminUsersGuarded.svelte";
  // Import components
  import EventModal from "./components/calendar/EventModal.svelte";
  import Footer from "./components/layout/Footer.svelte";
  import Header from "./components/layout/Header.svelte";
  // Import legal components
  import AgeVerificationModal from "./components/legal/AgeVerificationModal.svelte";
  import CookieBanner from "./components/legal/CookieBanner.svelte";
  // Import stores
  import { selectedEvent, showEventModal } from "./stores/calendar";
  import {
    ageVerificationStore,
    consentStore,
    showAgeVerification,
    showCookieBanner,
  } from "./stores/consent";
  import { transformApiEvent } from "./utils/eventTransform";

  // Define routes
  const routes = {
    // Admin routes - these need to come before the wildcard
    "/admin": AdminLogin,
    "/admin/login": AdminLogin,
    "/admin/events": AdminEventsGuarded,
    "/admin/messages": AdminMessagesGuarded,
    "/admin/users": AdminUsersGuarded,
    "/admin/profile": AdminProfileGuarded,
    // Regular routes
    "/": Home,
    "/events": Events,
    "/events/:id": Events, // Will show event modal
    "/about": About,
    "/resources": Resources,
    "/resources/safety-guide": SafetyGuide,
    "/resources/faq": Faq,
    "/learning-resources": LearningResources,
    "/code-of-conduct": CodeOfConduct,
    "/contact": Contact,
    "/privacy": Privacy,
    "/imprint": Imprint,
    "/submit-event": SubmitEvent,
    // Catch-all route must be last
    "*": NotFound,
  };

  // Handle deep linking to events
  onMount(() => {
    // Check age verification first
    if (!ageVerificationStore.isVerified()) {
      showAgeVerification.set(true);
    }

    // Check cookie consent
    consentStore.subscribe((state) => {
      if (!state.hasConsented && ageVerificationStore.isVerified()) {
        // Only show cookie banner after age verification
        showCookieBanner.set(true);
      } else {
        showCookieBanner.set(false);
      }
    });

    // Automatic redirect from non-hash URLs to hash URLs for consistency
    const currentPath = window.location.pathname;
    if (currentPath !== "/" && !window.location.hash) {
      // If we're on a non-root path without a hash, redirect to hash version
      const hashUrl = `/#${currentPath}`;
      console.log(
        `Redirecting from ${currentPath} to ${hashUrl} for consistency`,
      );
      window.location.replace(hashUrl);
      return;
    }

    const handleRouteChanged = (event: CustomEvent) => {
      const path = event.detail.location;
      const eventMatch = path.match(/^\/events\/(\d+)$/);

      if (eventMatch) {
        const eventId = parseInt(eventMatch[1]);
        // Load event data and show modal
        fetch(`/api/events/${eventId}`)
          .then((response) => {
            if (!response.ok) {
              throw new Error(`Event not found: ${response.status}`);
            }
            return response.json();
          })
          .then((result) => {
            const apiEvent = result.success ? result.data : null;
            if (apiEvent) {
              const transformedEvent = transformApiEvent(apiEvent);
              selectedEvent.set(transformedEvent);
              showEventModal.set(true);
            } else {
              throw new Error("Event data not found");
            }
          })
          .catch((error) => {
            console.error("Failed to load event:", error);
            // Could add a notification here for user feedback
            push("/events"); // Redirect to events page if event not found
          });
      }
    };

    // Listen for route changes
    window.addEventListener("routeEvent", handleRouteChanged as EventListener);

    return () => {
      window.removeEventListener(
        "routeEvent",
        handleRouteChanged as EventListener,
      );
    };
  });
</script>

<main id="main-content" class="min-h-screen bg-charcoal-900 text-smoke-50">
  <Header />

  <!-- Main content area with proper landmarks -->
  <div role="main" class="flex-1">
    <svelte:component this={router} {routes} />
  </div>

  <Footer />

  <!-- Event Modal -->
  {#if $showEventModal}
    <EventModal />
  {/if}

  <!-- Age Verification Modal -->
  {#if $showAgeVerification}
    <AgeVerificationModal />
  {/if}

  <!-- Cookie Consent Banner -->
  {#if $showCookieBanner}
    <CookieBanner />
  {/if}
</main>

<style>
  /* Global app styles */
  :global(html) {
    height: 100%;
  }

  :global(body) {
    height: 100%;
    margin: 0;
  }

  :global(#app) {
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  main {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }
</style>
