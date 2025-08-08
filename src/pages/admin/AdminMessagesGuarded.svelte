<script lang="ts">
  import AdminGuard from "../../components/admin/AdminGuard.svelte";
  import AdminMessages from "./AdminMessages.svelte";

  export let params = {};
</script>

<svelte:head>
  <title>Admin Nachrichten - Hypnose Stammtisch</title>
</svelte:head>

<AdminGuard component={AdminMessages} {params} />
