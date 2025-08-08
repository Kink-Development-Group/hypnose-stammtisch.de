<script lang="ts">
  import AdminGuard from "../../components/admin/AdminGuard.svelte";
  import AdminEvents from "./AdminEvents.svelte";

  export let params = {};
</script>

<svelte:head>
  <title>Admin Events - Hypnose Stammtisch</title>
</svelte:head>

<AdminGuard component={AdminEvents} {params} />
