<script lang="ts">
  export let className: string = "";
  export let size: "sm" | "md" | "lg" | "xl" = "md";
  export let showAnimation: boolean = false;

  // Size mapping
  const sizeClasses = {
    sm: "w-8 h-8",
    md: "w-12 h-12",
    lg: "w-16 h-16",
    xl: "w-24 h-24",
  };

  // Logo configuration
  $: logoSrc = "/LogoHypnoseStammtisch.de.svg";
  $: logoAlt = "Hypnose-Stammtisch.de Logo";
</script>

<div class="brand-logo {className}" class:animate-pulse={showAnimation}>
  <img
    src={logoSrc}
    alt={logoAlt}
    class="logo-image {sizeClasses[
      size
    ]} object-contain transition-all duration-300 hover:scale-105"
    loading="lazy"
    decoding="async"
  />
</div>

<style>
  .brand-logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .logo-image {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    transition: all 0.3s ease;
  }

  .logo-image:hover {
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
  }

  @media (prefers-reduced-motion: reduce) {
    .logo-image,
    .animate-pulse {
      animation: none !important;
      transition: none !important;
    }

    .logo-image:hover {
      transform: none !important;
    }
  }

  @keyframes pulse {
    0%,
    100% {
      opacity: 1;
    }
    50% {
      opacity: 0.8;
    }
  }

  .animate-pulse .logo-image {
    animation: pulse 2s infinite;
  }
</style>
