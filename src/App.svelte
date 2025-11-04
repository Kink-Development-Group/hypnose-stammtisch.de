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
  import Map from "./pages/Map.svelte";
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
  import AdminSecurityGuarded from "./pages/admin/AdminSecurityGuarded.svelte";
  import AdminStammtischLocationsGuarded from "./pages/admin/AdminStammtischLocationsGuarded.svelte";
  import AdminUsersGuarded from "./pages/admin/AdminUsersGuarded.svelte";
  // Import components
  import EventModal from "./components/calendar/EventModal.svelte";
  import Footer from "./components/layout/Footer.svelte";
  import Header from "./components/layout/Header.svelte";
  // Import stores
  import LearningResources from "./pages/LearningResources.svelte";
  import { selectedEvent, showEventModal } from "./stores/calendar";
  import { transformApiEvent } from "./utils/eventTransform";

  // Define routes
  const routes = {
    // Admin routes - these need to come before the wildcard
    "/admin": AdminLogin,
    "/admin/login": AdminLogin,
    "/admin/events": AdminEventsGuarded,
    "/admin/messages": AdminMessagesGuarded,
    "/admin/security": AdminSecurityGuarded,
    "/admin/users": AdminUsersGuarded,
    "/admin/stammtisch-locations": AdminStammtischLocationsGuarded,
    "/admin/profile": AdminProfileGuarded,
    // Regular routes
    "/": Home,
    "/events": Events,
    "/events/:id": Events, // Will show event modal
    "/map": Map,
    "/about": About,
    "/learning-resources": LearningResources,
    "/ressourcen": LearningResources,
    "/resources": Resources,
    "/ressourcen/safety-guide": SafetyGuide,
    "/ressourcen/faq": Faq,
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
    // Scroll to top on initial load (unless there's a hash anchor)
    const initialHash = window.location.hash;
    const hasAnchor =
      initialHash.split("#").length > 2 || initialHash.match(/#[^/]/);

    if (!hasAnchor) {
      window.scrollTo({ top: 0, behavior: "auto" });
    }

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

    // Scroll to top on route change (unless navigating to a hash anchor)
    const handleRouteChanged = (event: CustomEvent) => {
      const path = event.detail.location;

      // Extract the full hash from the URL
      const fullHash = window.location.hash;

      // Check if we're navigating to an anchor within the current page
      // Format: #/page#anchor or just #anchor
      const isHashAnchor =
        fullHash.includes("#") &&
        (fullHash.split("#").length > 2 ||
          (path && path.includes("#") && !path.startsWith("/")));

      // Scroll to top for normal page navigation (but not for in-page anchors)
      if (!isHashAnchor) {
        // Use setTimeout to ensure the route has changed before scrolling
        setTimeout(() => {
          window.scrollTo({ top: 0, behavior: "smooth" });
        }, 0);
      }

      // Handle event modal deep linking
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

<div id="app-wrapper" class="min-h-screen bg-charcoal-900 text-smoke-50">
  <Header />

  <!-- Main content area -->
  <main id="main-content" class="flex-1">
    <svelte:component this={router} {routes} />
  </main>

  <Footer />

  <!-- Event Modal -->
  {#if $showEventModal}
    <EventModal />
  {/if}
</div>

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

  #app-wrapper {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }
</style>
