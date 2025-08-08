<script lang="ts">
  import AdminGuard from "../../components/admin/AdminGuard.svelte";
  import AdminUsers from "./AdminUsers.svelte";

  export let params = {};
</script>

<svelte:head>
  <title>Admin Benutzer - Hypnose Stammtisch</title>
</svelte:head>

<AdminGuard component={AdminUsers} {params} />
