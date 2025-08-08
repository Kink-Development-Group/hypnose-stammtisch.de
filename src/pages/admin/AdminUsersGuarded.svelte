<script lang="ts">
  import AdminGuard from "../../components/admin/AdminGuard.svelte";
  import AdminUsersPage from "./AdminUsersPage.svelte";

  export let params = {};
</script>

<svelte:head>
  <title>Admin Benutzer - Hypnose Stammtisch</title>
</svelte:head>

<AdminGuard component={AdminUsersPage} {params} />
