{{-- resources/views/components/application-logo.blade.php --}}
{{--
    This is a complex multi-color SVG.
    If this is an official MOTAC logo with fixed colors, it should remain as is.
    If it needs to adapt to the MOTAC theme (e.g., primary blue, secondary maroon):
    - The hardcoded 'fill' colors in <path> elements and gradient <stop> colors would need to be manually changed.
    - Alternatively, for simpler SVGs, 'currentColor' can be used for fills to inherit CSS color.
      This is more complex for multi-color SVGs like this one.
--}}
<svg viewbox="0 0 148 80" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
    {{ $attributes }}>
    <defs>
        <lineargradient id="a1_applogo" x1="46.49" x2="62.46" y1="53.39" y2="48.2"
            gradientunits="userSpaceOnUse">
            <stop stop-opacity=".25" offset="0"></stop>
            <stop stop-opacity=".1" offset=".3"></stop>
            <stop stop-opacity="0" offset=".9"></stop>
        </lineargradient>
        <lineargradient id="e2_applogo" x1="76.9" x2="92.64" y1="26.38" y2="31.49"
            xlink:href="#a1_applogo"></lineargradient>
        <lineargradient id="d3_applogo" x1="107.12" x2="122.74" y1="53.41" y2="48.33"
            xlink:href="#a1_applogo"></lineargradient>
    </defs>
    {{-- The main fill of this path could potentially be set to var(--motac-primary) if the logo design allows for a dominant theme color. --}}
    <path style="fill: #0055A4;" {{-- Example: Forcing MOTAC Blue for the main shape. This might not be desired if the logo has specific brand colors. --}} transform="translate(-.1)"
        d="M121.36,0,104.42,45.08,88.71,3.28A5.09,5.09,0,0,0,83.93,0H64.27A5.09,5.09,0,0,0,59.5,3.28L43.79,45.08,26.85,0H.1L29.43,76.74A5.09,5.09,0,0,0,34.19,80H53.39a5.09,5.09,0,0,0,4.77-3.26L74.1,35l16,41.74A5.09,5.09,0,0,0,94.82,80h18.95a5.09,5.09,0,0,0,4.76-3.24L148.1,0Z">
    </path>
    {{-- The gradient fills are specific and may be part of the fixed logo design --}}
    <path transform="translate(-.1)" d="M52.19,22.73l-8.4,22.35L56.51,78.94a5,5,0,0,0,1.64-2.19l7.34-19.2Z"
        fill="url(#a1_applogo)"></path>
    <path transform="translate(-.1)" d="M95.73,22l-7-18.69a5,5,0,0,0-1.64-2.21L74.1,35l8.33,21.79Z"
        fill="url(#e2_applogo)"></path>
    <path transform="translate(-.1)" d="M112.73,23l-8.31,22.12,12.66,33.7a5,5,0,0,0,1.45-2l7.3-18.93Z"
        fill="url(#d3_applogo)">
    </path>
</svg>
