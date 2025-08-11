<script lang="ts">
  import { link } from "svelte-spa-router";
  import EventSubmissionForm from "../components/forms/EventSubmissionForm.svelte";
</script>

<section class="container mx-auto px-4 py-12 max-w-4xl">
  <h1 class="h1 mb-6">Event einreichen</h1>
  <p class="lead mb-10 text-slate-300 max-w-2xl">
    Reiche Dein Community-Event für den Hypnose Stammtisch Kalender ein. Bitte
    beachte unseren
    <a href="/code-of-conduct" use:link class="underline hover:text-primary-300"
      >Verhaltenskodex</a
    >
    und stelle sicher, dass alle Pflichtfelder korrekt ausgefüllt sind.
  </p>
  <EventSubmissionForm />
</section>

<section class="container mx-auto px-4 mt-16 mb-24">
  <div class="flex flex-wrap gap-4">
    <a href="/events" use:link class="btn btn-outline">Zurück zu Events</a>
    <a href="/resources/safety-guide" use:link class="btn btn-outline"
      >Safety Guide</a
    >
    <a href="/resources/faq" use:link class="btn btn-outline">FAQ</a>
    <a href="/contact" use:link class="btn btn-outline">Kontakt</a>
  </div>
</section>
