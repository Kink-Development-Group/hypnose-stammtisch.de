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
  import NotFound from "./pages/NotFound.svelte";
  import Privacy from "./pages/Privacy.svelte";
  import Resources from "./pages/Resources.svelte";
  // Import components
  import EventModal from "./components/calendar/EventModal.svelte";
  import Footer from "./components/layout/Footer.svelte";
  import Header from "./components/layout/Header.svelte";
  // Import stores
  import { selectedEvent, showEventModal } from "./stores/calendar";
  import { transformApiEvent } from "./utils/eventTransform";

  // Define routes
  const routes = {
    "/": Home,
    "/events": Events,
    "/events/:id": Events, // Will show event modal
    "/about": About,
    "/resources": Resources,
    "/code-of-conduct": CodeOfConduct,
    "/contact": Contact,
    "/privacy": Privacy,
    "/imprint": Imprint,
    "*": NotFound,
  };

  // Handle deep linking to events
  onMount(() => {
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
    {#await import('svelte-spa-router')}
      <div class="flex items-center justify-center min-h-[50vh]">
        <div class="animate-pulse">
          <div
            class="w-16 h-16 border-4 border-accent-400 border-t-transparent rounded-full animate-spin"
          ></div>
        </div>
      </div>
    {:then}
      <svelte:component this={router} {routes} />
    {:catch error}
      <div class="container mx-auto px-4 py-16 text-center">
        <h1 class="text-3xl font-display font-bold text-boundaries mb-4">
          Fehler beim Laden der Anwendung
        </h1>
        <p class="text-smoke-300 mb-8">
          {error?.message || "Ein unerwarteter Fehler ist aufgetreten."}
        </p>
        <button
          class="btn btn-primary"
          on:click={() => window.location.reload()}
        >
          Seite neu laden
        </button>
      </div>
    {/await}
  </div>

  <Footer />

  <!-- Event Modal -->
  {#if $showEventModal}
    <EventModal />
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
