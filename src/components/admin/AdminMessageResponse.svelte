<script lang="ts">
  import { onMount } from "svelte";
  import { AdminAPI } from "../../stores/admin";

  export let messageId: number;
  export let contactEmail: string;
  export let contactName: string = "";
  export let originalSubject: string = "";

  interface EmailAddress {
    id: number;
    name: string;
    email: string;
    description: string;
  }

  interface MessageResponse {
    id: number;
    message_id: number;
    from_email_id: number;
    from_email: string;
    from_name: string;
    to_email: string;
    subject: string;
    body: string;
    sent_at: string;
  }

  let emailAddresses: EmailAddress[] = [];
  let responses: MessageResponse[] = [];
  let loading = false;
  let showComposer = false;

  // Form data
  let selectedEmailId: number | null = null;
  let subject = "";
  let body = "";
  let sending = false;

  onMount(async () => {
    await loadEmailAddresses();
    await loadResponses();

    // Set default subject
    if (originalSubject) {
      subject = originalSubject.startsWith("Re: ")
        ? originalSubject
        : `Re: ${originalSubject}`;
    } else {
      subject = "Re: Ihre Nachricht an den Hypnose-Stammtisch";
    }
  });

  async function loadEmailAddresses() {
    try {
      const result = await AdminAPI.getEmailAddresses();
      if (result.success) {
        emailAddresses = result.data;
        // Select first email by default
        if (emailAddresses.length > 0 && !selectedEmailId) {
          selectedEmailId = emailAddresses[0].id;
        }
      }
    } catch (error) {
      console.error("Failed to load email addresses:", error);
    }
  }

  async function loadResponses() {
    loading = true;
    try {
      const result = await AdminAPI.getMessageResponses(messageId);
      if (result.success) {
        responses = result.data;
      }
    } catch (error) {
      console.error("Failed to load responses:", error);
    } finally {
      loading = false;
    }
  }

  async function sendResponse() {
    if (!selectedEmailId || !subject.trim() || !body.trim()) {
      return;
    }

    sending = true;
    try {
      const result = await AdminAPI.sendMessageResponse(
        messageId,
        selectedEmailId,
        subject.trim(),
        body.trim(),
      );
      if (result.success) {
        // Reset form
        body = "";
        showComposer = false;
        await loadResponses();
      }
    } catch (error) {
      console.error("Failed to send response:", error);
    } finally {
      sending = false;
    }
  }

  function openComposer() {
    showComposer = true;
    // Set default greeting
    if (!body.trim()) {
      const greeting = contactName ? `Moin ${contactName},` : "Moin,";
      body = `${greeting}\n\nvielen Dank für Deine Nachricht.\n\n\n\nMit freundlichen Grüßen\nDein Hypnose-Stammtisch.de Team`;
    }
  }

  function formatDate(dateString: string) {
    return new Date(dateString).toLocaleString("de-DE");
  }
</script>

<div class="admin-message-response">
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-semibold">E-Mail Antworten</h3>
    <button
      on:click={openComposer}
      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
    >
      Antwort schreiben
    </button>
  </div>

  <!-- Email Composer -->
  {#if showComposer}
    <div class="mb-6 p-4 bg-gray-50 rounded-lg border">
      <div class="space-y-4">
        <!-- From Email Selection -->
        <div>
          <label
            for="from-email-select"
            class="block text-sm font-medium text-gray-700 mb-2"
          >
            Absender
          </label>
          <select
            id="from-email-select"
            bind:value={selectedEmailId}
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            {#each emailAddresses as emailAddr (emailAddr.id)}
              <option value={emailAddr.id}>
                {emailAddr.name} ({emailAddr.email}) - {emailAddr.description}
              </option>
            {/each}
          </select>
        </div>

        <!-- To Email (readonly) -->
        <div>
          <label
            for="to-email-input"
            class="block text-sm font-medium text-gray-700 mb-2"
          >
            Empfänger
          </label>
          <input
            id="to-email-input"
            type="email"
            value={contactEmail}
            readonly
            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600"
          />
        </div>

        <!-- Subject -->
        <div>
          <label
            for="subject-input"
            class="block text-sm font-medium text-gray-700 mb-2"
          >
            Betreff
          </label>
          <input
            id="subject-input"
            type="text"
            bind:value={subject}
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="E-Mail Betreff"
          />
        </div>

        <!-- Body -->
        <div>
          <label
            for="body-textarea"
            class="block text-sm font-medium text-gray-700 mb-2"
          >
            Nachricht
          </label>
          <textarea
            id="body-textarea"
            bind:value={body}
            rows="10"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
            placeholder="Ihre Antwort..."
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <button
            on:click={() => (showComposer = false)}
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
          >
            Abbrechen
          </button>
          <button
            on:click={sendResponse}
            disabled={!selectedEmailId ||
              !subject.trim() ||
              !body.trim() ||
              sending}
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
          >
            {#if sending}
              <div
                class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"
              ></div>
            {/if}
            E-Mail senden
          </button>
        </div>
      </div>
    </div>
  {/if}

  <!-- Sent Responses -->
  <div>
    <h4 class="text-md font-medium mb-3">
      Gesendete Antworten ({responses.length})
    </h4>

    {#if loading}
      <div class="text-center py-4">
        <div
          class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"
        ></div>
        <span class="ml-2 text-gray-600">Lade Antworten...</span>
      </div>
    {:else if responses.length === 0}
      <div class="text-center py-8 text-gray-500">
        <p>Noch keine Antworten gesendet.</p>
      </div>
    {:else}
      <div class="space-y-4">
        {#each responses as response (response.id)}
          <div class="border border-gray-200 rounded-lg p-4 bg-white">
            <div class="flex items-start justify-between mb-3">
              <div>
                <div class="font-medium text-gray-900">
                  {response.subject}
                </div>
                <div class="text-sm text-gray-600 mt-1">
                  Von: {response.from_name} ({response.from_email})
                </div>
                <div class="text-sm text-gray-600">
                  An: {response.to_email}
                </div>
              </div>
              <div class="text-xs text-gray-500">
                {formatDate(response.sent_at)}
              </div>
            </div>

            <div
              class="text-gray-800 whitespace-pre-wrap text-sm bg-gray-50 p-3 rounded border"
            >
              {response.body}
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </div>
</div>

<style>
  .admin-message-response {
    max-width: 100%;
  }
</style>
