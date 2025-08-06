<script lang="ts">
  export let className: string = "";

  // Props for customization
  export let primaryColor: string = "#3C2A89";
  export let accentColor: string = "#41F2C0";
  export let secondaryColor: string = "#C22064";
  export let showAnimation: boolean = false;
</script>

<svg
  class="spiral-logo {className}"
  width="100"
  height="100"
  viewBox="0 0 100 100"
  xmlns="http://www.w3.org/2000/svg"
  role="img"
  aria-label="Hypnose Stammtisch Logo - Spiral Eye"
>
  <defs>
    <radialGradient
      id="spiralGradient-{Math.random().toString(36).substr(2, 9)}"
      cx="50%"
      cy="50%"
      r="50%"
    >
      <stop offset="0%" style="stop-color:{accentColor};stop-opacity:1" />
      <stop offset="60%" style="stop-color:{primaryColor};stop-opacity:0.8" />
      <stop offset="100%" style="stop-color:#111418;stop-opacity:0" />
    </radialGradient>
  </defs>

  <!-- Spiral rings with animation -->
  <g class="spiral-rings" class:animate-spiral={showAnimation}>
    <circle
      cx="50"
      cy="50"
      r="45"
      fill="none"
      stroke={primaryColor}
      stroke-width="2"
      opacity="0.6"
    />
    <circle
      cx="50"
      cy="50"
      r="35"
      fill="none"
      stroke={accentColor}
      stroke-width="1.5"
      opacity="0.7"
    />
    <circle
      cx="50"
      cy="50"
      r="25"
      fill="none"
      stroke={secondaryColor}
      stroke-width="1"
      opacity="0.8"
    />
    <circle
      cx="50"
      cy="50"
      r="15"
      fill="none"
      stroke={accentColor}
      stroke-width="0.8"
      opacity="0.9"
    />
  </g>

  <!-- Central iris -->
  <circle
    cx="50"
    cy="50"
    r="8"
    fill="url(#spiralGradient-{Math.random().toString(36).substr(2, 9)})"
  />
  <circle cx="50" cy="50" r="3" fill={accentColor} />
</svg>

<style>
  .spiral-logo {
    transition: all 0.3s ease;
  }

  .spiral-logo:hover .spiral-rings {
    animation: spiral 8s linear infinite;
  }

  @media (prefers-reduced-motion: reduce) {
    .spiral-logo:hover .spiral-rings,
    .animate-spiral {
      animation: none !important;
    }
  }

  @keyframes spiral {
    from {
      transform: rotate(0deg);
    }
    to {
      transform: rotate(360deg);
    }
  }
</style>
