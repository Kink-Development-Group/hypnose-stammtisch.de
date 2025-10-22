<script lang="ts">
  import AdminGuard from "../../components/admin/AdminGuard.svelte";
  import AdminSecurity from "./AdminSecurity.svelte";

  export let params = {};
</script>

<svelte:head>
  <title>Admin Sicherheit - Hypnose Stammtisch</title>
</svelte:head>

<AdminGuard component={AdminSecurity} {params} />
