<script lang="ts">
  import { onMount } from "svelte";
  import User from "../../classes/User";
  import { Role } from "../../enums/role";
  import { UserHelpers } from "../../utils/userHelpers";

  interface UserFormData {
    username: string;
    email: string;
    password?: string;
    role: Role;
    is_active: boolean;
  }

  let users: User[] = [];
  let loading = true;
  let error = "";
  let showCreateForm = false;
  let editingUser: User | null = null;
  let permissions = { can_manage_users: false };

  let formData: UserFormData = {
    username: "",
    email: "",
    password: "",
    role: Role.ADMIN,
    is_active: true,
  };

  onMount(async () => {
    await checkPermissions();
    if (permissions.can_manage_users) {
      await loadUsers();
    }
  });

  async function checkPermissions() {
    try {
      console.log("AdminUsers: Checking permissions...");
      const response = await fetch("/api/admin/users/permissions", {
        credentials: "include",
      });

      console.log("AdminUsers: Permissions response status:", response.status);

      if (response.ok) {
        permissions = await response.json();
        console.log("AdminUsers: Permissions loaded:", permissions);
      } else {
        console.error(
          "AdminUsers: Permissions request failed:",
          response.status,
        );
        const errorText = await response.text();
        console.error("AdminUsers: Error response:", errorText);
      }
    } catch (err) {
      console.error("AdminUsers: Failed to check permissions:", err);
    }
  }

  async function loadUsers() {
    try {
      loading = true;
      error = "";

      const response = await fetch("/api/admin/users", {
        credentials: "include",
      });

      if (!response.ok) {
        throw new Error("Failed to load users");
      }

      const apiUsers = await response.json();
      users = UserHelpers.fromApiArray(apiUsers);
    } catch (err) {
      error = "Fehler beim Laden der Admin-Benutzer";
      console.error("Error loading users:", err);
    } finally {
      loading = false;
    }
  }

  async function createUser() {
    try {
      error = "";

      const response = await fetch("/api/admin/users", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(formData),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || "Failed to create user");
      }

      await loadUsers();
      resetForm();
      showCreateForm = false;
    } catch (err) {
      error =
        err instanceof Error
          ? err.message
          : "Fehler beim Erstellen des Benutzers";
      console.error("Error creating user:", err);
    }
  }

  async function updateUser() {
    if (!editingUser) return;

    try {
      error = "";

      const updateData: Partial<UserFormData> = { ...formData };
      if (!updateData.password) {
        updateData.password = undefined; // Don't update password if empty
      }

      const response = await fetch(`/api/admin/users/${editingUser.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(updateData),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || "Failed to update user");
      }

      await loadUsers();
      resetForm();
      editingUser = null;
    } catch (err) {
      error =
        err instanceof Error
          ? err.message
          : "Fehler beim Aktualisieren des Benutzers";
      console.error("Error updating user:", err);
    }
  }

  async function deleteUser(user: User) {
    if (
      !confirm(
        `Sind Sie sicher, dass Sie den Benutzer "${user.username}" löschen möchten?`,
      )
    ) {
      return;
    }

    try {
      error = "";

      const response = await fetch(`/api/admin/users/${user.id}`, {
        method: "DELETE",
        credentials: "include",
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || "Failed to delete user");
      }

      await loadUsers();
    } catch (err) {
      error =
        err instanceof Error
          ? err.message
          : "Fehler beim Löschen des Benutzers";
      console.error("Error deleting user:", err);
    }
  }

  function startEdit(user: User) {
    editingUser = user;
    formData = {
      username: user.username,
      email: user.email,
      password: "", // Always empty for security
      role: user.role,
      is_active: user.is_active,
    };
  }

  function resetForm() {
    formData = {
      username: "",
      email: "",
      password: "",
      role: Role.ADMIN,
      is_active: true,
    };
  }

  function cancelEdit() {
    editingUser = null;
    resetForm();
  }

  function formatDate(date: Date | string | null): string {
    return UserHelpers.formatDate(date);
  }
</script>

{#if !permissions.can_manage_users}
  <div class="max-w-4xl mx-auto p-6">
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
      <h3 class="text-lg font-medium text-red-800 mb-2">Keine Berechtigung</h3>
      <p class="text-red-700">
        Sie haben keine Berechtigung, Admin-Benutzer zu verwalten. Nur
        Head-Admins können diese Funktion nutzen.
      </p>
      <div class="mt-4 text-sm text-gray-600 bg-gray-100 p-2 rounded">
        <strong>Debug Info:</strong><br />
        Current permissions: {JSON.stringify(permissions)}<br />
        can_manage_users: {permissions.can_manage_users}<br />
        Überprüfen Sie die Browser-Konsole für weitere Details.
      </div>
    </div>
  </div>
{:else}
  <div class="max-w-6xl mx-auto p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">
        Admin-Benutzer verwalten
      </h1>
      <p class="text-gray-600">
        Verwalten Sie Admin-Benutzer, deren Rollen und Berechtigungen.
      </p>
    </div>

    {#if error}
      <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-700">{error}</p>
      </div>
    {/if}

    <!-- Create/Edit Form -->
    {#if showCreateForm || editingUser}
      <div class="mb-6 bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          {editingUser
            ? "Benutzer bearbeiten"
            : "Neuen Admin-Benutzer erstellen"}
        </h2>

        <form
          on:submit|preventDefault={editingUser ? updateUser : createUser}
          class="grid grid-cols-1 md:grid-cols-2 gap-4"
        >
          <div>
            <label
              for="username"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Benutzername *
            </label>
            <input
              type="text"
              id="username"
              bind:value={formData.username}
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label
              for="email"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              E-Mail *
            </label>
            <input
              type="email"
              id="email"
              bind:value={formData.email}
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label
              for="password"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Passwort {editingUser ? "(leer lassen, um nicht zu ändern)" : "*"}
            </label>
            <input
              type="password"
              id="password"
              bind:value={formData.password}
              required={!editingUser}
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label
              for="role"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Rolle *
            </label>
            <select
              id="role"
              bind:value={formData.role}
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value={Role.MODERATOR}>Moderator</option>
              <option value={Role.ADMIN}>Administrator</option>
              <option value={Role.HEADADMIN}>Head Admin</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="flex items-center">
              <input
                type="checkbox"
                bind:checked={formData.is_active}
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Benutzer ist aktiv</span>
            </label>
          </div>

          <div class="md:col-span-2 flex gap-3">
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              {editingUser ? "Aktualisieren" : "Erstellen"}
            </button>

            <button
              type="button"
              on:click={() => {
                if (editingUser) {
                  cancelEdit();
                } else {
                  showCreateForm = false;
                  resetForm();
                }
              }}
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Abbrechen
            </button>
          </div>
        </form>
      </div>
    {/if}

    <!-- Action Buttons -->
    {#if !showCreateForm && !editingUser}
      <div class="mb-6">
        <button
          on:click={() => {
            showCreateForm = true;
            resetForm();
          }}
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          Neuen Admin-Benutzer erstellen
        </button>
      </div>
    {/if}

    <!-- Users Table -->
    {#if loading}
      <div class="text-center py-8">
        <div
          class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
        ></div>
        <p class="mt-2 text-gray-600">Lade Admin-Benutzer...</p>
      </div>
    {:else if users.length === 0}
      <div class="text-center py-8">
        <p class="text-gray-600">Keine Admin-Benutzer gefunden.</p>
      </div>
    {:else}
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Benutzer
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Rolle
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Status
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Letzter Login
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Erstellt
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Aktionen
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            {#each users as user (user.id)}
              <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      {user.username}
                    </div>
                    <div class="text-sm text-gray-500">{user.email}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-medium rounded-full {UserHelpers.getRoleBadgeClass(
                      user.role,
                    )}"
                  >
                    {UserHelpers.getRoleDisplayName(user.role)}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-medium rounded-full {user.is_active
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800'}"
                  >
                    {user.is_active ? "Aktiv" : "Inaktiv"}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {formatDate(user.last_login)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {formatDate(user.created_at)}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                >
                  <button
                    on:click={() => startEdit(user)}
                    class="text-blue-600 hover:text-blue-900 mr-3"
                  >
                    Bearbeiten
                  </button>
                  <button
                    on:click={() => deleteUser(user)}
                    class="text-red-600 hover:text-red-900"
                  >
                    Löschen
                  </button>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {/if}
  </div>
{/if}
