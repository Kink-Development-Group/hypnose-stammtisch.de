<script lang="ts" context="module">
  /**
   * CAPTCHA Configuration Interface
   */
  export interface CaptchaConfig {
    enabled: boolean;
    provider?: "turnstile" | "hcaptcha" | "recaptcha";
    siteKey?: string;
  }

  const SCRIPT_URLS: Record<string, string> = {
    turnstile: "https://challenges.cloudflare.com/turnstile/v0/api.js",
    hcaptcha: "https://js.hcaptcha.com/1/api.js",
    recaptcha: "https://www.google.com/recaptcha/api.js",
  };

  let cachedConfig: CaptchaConfig | null = null;
  // Using a plain object instead of Set since this is module-level state
  // that doesn't need Svelte reactivity
  const loadedScripts: Record<string, boolean> = {};
</script>

<script lang="ts">
  // The CAPTCHA provider APIs (turnstile, hcaptcha, grecaptcha) are loaded dynamically
  // and their types are not available, so we use 'any' for window access

  import { createEventDispatcher, onDestroy, onMount } from "svelte";

  export let action: string = "submit";
  export let invisible: boolean = true;
  export let theme: "light" | "dark" | "auto" = "auto";
  export let size: "normal" | "compact" = "normal";

  const dispatch = createEventDispatcher<{
    token: string;
    error: string;
    ready: void;
    expired: void;
  }>();

  let container: HTMLDivElement;
  let widgetId: string | number | null = null;
  let config: CaptchaConfig | null = null;
  let isReady = false;
  let error = "";
  let currentToken = "";

  async function fetchConfig(): Promise<CaptchaConfig> {
    if (cachedConfig) return cachedConfig;
    try {
      const response = await fetch("/api/captcha/config");
      if (!response.ok) throw new Error("Failed to fetch CAPTCHA config");
      cachedConfig = await response.json();
      return cachedConfig as CaptchaConfig;
    } catch {
      return { enabled: false };
    }
  }

  function loadScript(provider: string): Promise<void> {
    const url = SCRIPT_URLS[provider];
    if (!url) return Promise.reject(new Error(`Unknown provider: ${provider}`));
    if (loadedScripts[provider]) return Promise.resolve();

    return new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = url + "?render=explicit";
      script.async = true;
      script.defer = true;
      script.onload = () => {
        loadedScripts[provider] = true;
        resolve();
      };
      script.onerror = () => reject(new Error(`Failed to load ${provider}`));
      document.head.appendChild(script);
    });
  }

  async function initWidget() {
    if (!config?.enabled || !config.siteKey || !container) return;
    const provider = config.provider || "turnstile";
    try {
      await loadScript(provider);
      await waitForGlobal(provider);
      renderWidget(provider);
    } catch (e) {
      error = e instanceof Error ? e.message : "CAPTCHA init failed";
      dispatch("error", error);
    }
  }

  function waitForGlobal(provider: string): Promise<void> {
    const globalMap: Record<string, string> = {
      turnstile: "turnstile",
      hcaptcha: "hcaptcha",
      recaptcha: "grecaptcha",
    };
    return new Promise((resolve, reject) => {
      let attempts = 0;
      const check = () => {
        if ((window as any)[globalMap[provider]]) resolve();
        else if (attempts++ >= 50)
          reject(new Error(`${provider} did not load`));
        else setTimeout(check, 100);
      };
      check();
    });
  }

  function renderWidget(provider: string) {
    if (!config?.siteKey || !container) return;
    if (provider === "turnstile") {
      widgetId = (window as any).turnstile.render(container, {
        sitekey: config.siteKey,
        theme: theme === "auto" ? "auto" : theme,
        size: invisible ? "invisible" : size,
        callback: (token: string) => {
          currentToken = token;
          dispatch("token", token);
        },
        "expired-callback": () => {
          currentToken = "";
          dispatch("expired");
        },
        "error-callback": (code: string) => {
          error = `CAPTCHA error: ${code}`;
          dispatch("error", error);
        },
      });
    } else if (provider === "hcaptcha") {
      widgetId = (window as any).hcaptcha.render(container, {
        sitekey: config.siteKey,
        theme: theme === "auto" ? "light" : theme,
        size: invisible ? "invisible" : size,
        callback: (token: string) => {
          currentToken = token;
          dispatch("token", token);
        },
        "expired-callback": () => {
          currentToken = "";
          dispatch("expired");
        },
        "error-callback": (code: string) => {
          error = `CAPTCHA error: ${code}`;
          dispatch("error", error);
        },
      });
    } else if (provider === "recaptcha") {
      (window as any).grecaptcha.ready(() => {
        isReady = true;
        dispatch("ready");
      });
      return;
    }
    isReady = true;
    dispatch("ready");
  }

  export async function execute(): Promise<string> {
    if (!config?.enabled) return "";
    if (!isReady) throw new Error("CAPTCHA not ready");
    const provider = config.provider || "turnstile";

    return new Promise((resolve, reject) => {
      if (provider === "turnstile") {
        if (currentToken) {
          resolve(currentToken);
        } else {
          (window as any).turnstile.execute(container, {
            callback: (token: string) => {
              currentToken = token;
              dispatch("token", token);
              resolve(token);
            },
            "error-callback": () => reject(new Error("CAPTCHA failed")),
          });
        }
      } else if (provider === "hcaptcha") {
        (window as any).hcaptcha
          .execute(widgetId, { async: true })
          .then(({ response }: { response: string }) => {
            currentToken = response;
            dispatch("token", response);
            resolve(response);
          })
          .catch(reject);
      } else if (provider === "recaptcha") {
        (window as any).grecaptcha
          .execute(config!.siteKey, { action })
          .then((token: string) => {
            currentToken = token;
            dispatch("token", token);
            resolve(token);
          })
          .catch(reject);
      } else {
        reject(new Error(`Unknown provider: ${provider}`));
      }
    });
  }

  export function reset() {
    if (!config?.enabled || !isReady) return;
    currentToken = "";
    const provider = config.provider || "turnstile";
    if (provider === "turnstile" && widgetId !== null) {
      (window as any).turnstile.reset(widgetId);
    } else if (provider === "hcaptcha" && widgetId !== null) {
      (window as any).hcaptcha.reset(widgetId);
    }
  }

  export function getToken(): string {
    return currentToken;
  }

  export function isEnabled(): boolean {
    return config?.enabled ?? false;
  }

  export function isWidgetReady(): boolean {
    return isReady;
  }

  onMount(async () => {
    config = await fetchConfig();
    if (config.enabled) {
      await initWidget();
    } else {
      isReady = true;
      dispatch("ready");
    }
  });

  onDestroy(() => {
    if (widgetId !== null && config?.enabled) {
      const provider = config.provider || "turnstile";
      try {
        if (provider === "turnstile") {
          (window as any).turnstile?.remove?.(widgetId);
        } else if (provider === "hcaptcha") {
          (window as any).hcaptcha?.remove?.(widgetId);
        }
      } catch {
        // Ignore cleanup errors
      }
    }
  });
</script>

{#if config?.enabled}
  <div
    bind:this={container}
    class="captcha-container"
    class:invisible
    data-testid="captcha-widget"
  ></div>
{/if}

{#if error}
  <div class="captcha-error text-sm text-red-600 mt-1" role="alert">
    {error}
  </div>
{/if}

<style>
  .captcha-container {
    margin: 0.5rem 0;
  }
  .captcha-container.invisible {
    position: absolute;
    width: 0;
    height: 0;
    overflow: hidden;
    opacity: 0;
    pointer-events: none;
  }
</style>
