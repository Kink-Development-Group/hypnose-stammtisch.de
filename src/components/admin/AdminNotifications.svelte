<script lang="ts">
  import { onMount } from "svelte";

  interface AdminNotification {
    id: string;
    type: "success" | "error" | "info" | "warning";
    message: string;
    duration?: number;
  }

  let adminNotifications: AdminNotification[] = [];

  onMount(() => {
    // Überwache Console-Logs für Admin-Benachrichtigungen
    const originalLog = console.log;
    const originalError = console.error;

    console.log = (...args: any[]) => {
      const message = args.join(" ");
      if (message.startsWith("✅ Success:")) {
        addAdminNotification({
          type: "success",
          message: message.replace("✅ Success: ", ""),
          duration: 3000,
        });
      } else if (message.startsWith("ℹ️ Info:")) {
        addAdminNotification({
          type: "info",
          message: message.replace("ℹ️ Info: ", ""),
          duration: 2000,
        });
      }
      originalLog.apply(console, args);
    };

    console.error = (...args: any[]) => {
      const message = args.join(" ");
      if (message.startsWith("❌ Error:")) {
        addAdminNotification({
          type: "error",
          message: message.replace("❌ Error: ", ""),
          duration: 5000,
        });
      }
      originalError.apply(console, args);
    };

    return () => {
      console.log = originalLog;
      console.error = originalError;
    };
  });

  function addAdminNotification(notification: Omit<AdminNotification, "id">) {
    const id = Math.random().toString(36).substr(2, 9);
    const newNotification: AdminNotification = {
      ...notification,
      id,
      duration: notification.duration || 4000,
    };

    adminNotifications = [...adminNotifications, newNotification];

    // Auto-remove nach duration
    if (newNotification.duration && newNotification.duration > 0) {
      setTimeout(() => {
        removeAdminNotification(id);
      }, newNotification.duration);
    }
  }

  function removeAdminNotification(id: string) {
    adminNotifications = adminNotifications.filter((n) => n.id !== id);
  }

  function getNotificationClasses(type: string): string {
    const baseClasses =
      "p-4 rounded-lg shadow-lg border-l-4 transition-all duration-300 ease-in-out";

    switch (type) {
      case "success":
        return `${baseClasses} bg-green-50 dark:bg-green-900/30 border-green-400 dark:border-green-600 text-green-800 dark:text-green-200`;
      case "error":
        return `${baseClasses} bg-red-50 dark:bg-red-900/30 border-red-400 dark:border-red-600 text-red-800 dark:text-red-200`;
      case "warning":
        return `${baseClasses} bg-yellow-50 dark:bg-yellow-900/30 border-yellow-400 dark:border-yellow-600 text-yellow-800 dark:text-yellow-200`;
      case "info":
        return `${baseClasses} bg-blue-50 dark:bg-blue-900/30 border-blue-400 dark:border-blue-600 text-blue-800 dark:text-blue-200`;
      default:
        return `${baseClasses} bg-gray-50 dark:bg-charcoal-700 border-gray-400 dark:border-charcoal-500 text-gray-800 dark:text-smoke-200`;
    }
  }

  function getNotificationIcon(type: string): string {
    switch (type) {
      case "success":
        return "✅";
      case "error":
        return "❌";
      case "warning":
        return "⚠️";
      case "info":
        return "ℹ️";
      default:
        return "📝";
    }
  }
</script>

<!-- Admin Notification Container -->
{#if adminNotifications.length > 0}
  <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm">
    {#each adminNotifications as notification (notification.id)}
      <div
        class={getNotificationClasses(notification.type)}
        role="alert"
        aria-live="polite"
      >
        <div class="flex items-start">
          <span class="text-lg mr-3 flex-shrink-0 mt-0.5">
            {getNotificationIcon(notification.type)}
          </span>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium break-words">
              {notification.message}
            </p>
          </div>

          <button
            on:click={() => removeAdminNotification(notification.id)}
            class="ml-3 flex-shrink-0 text-lg leading-none hover:opacity-70 transition-opacity"
            aria-label="Benachrichtigung schließen"
          >
            ×
          </button>
        </div>
      </div>
    {/each}
  </div>
{/if}

<style>
  /* Smooth entry/exit animations */
  :global(.admin-notification-enter) {
    opacity: 0;
    transform: translateX(100%);
  }

  :global(.admin-notification-enter-active) {
    opacity: 1;
    transform: translateX(0);
    transition:
      opacity 300ms ease-out,
      transform 300ms ease-out;
  }

  :global(.admin-notification-exit) {
    opacity: 1;
    transform: translateX(0);
  }

  :global(.admin-notification-exit-active) {
    opacity: 0;
    transform: translateX(100%);
    transition:
      opacity 300ms ease-in,
      transform 300ms ease-in;
  }
</style>
