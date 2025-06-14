{{-- resources/views/includes/svg/macros.blade.php --}}
{{--
    MOTAC SVG Graphic.
    To theme this SVG with MOTAC colors, you would need to:
    1. Identify which existing colors map to your theme's palette
       (e.g., --motac-primary, --motac-success, --motac-secondary, --motac-text-muted, etc.).
    2. Replace the hardcoded 'fill' attributes below with the desired MOTAC hex codes
       or, if used as an inline SVG within a Blade component supporting it, potentially CSS variables.
    Example: fill="#189A57" might become fill="var(--motac-success)" or a specific hex like "#28a745".
--}}
<svg width="57" height="{{ $height ?? '78.5' }}" viewBox="0 0 57 78.5" fill="none" xmlns="http://www.w3.org/2000/svg">
  <g style="isolation: isolate">
    <g id="Layer_1" data-name="Layer 1">
      <g>
        <g>
          {{-- Example: Potential MOTAC Success or Primary color --}}
          <path d="M78.5,29.2V57.9c-3.6-7.2-19.7-12.1-19.7-12.1V26.9c0-11.4,16.4-13.5,19.1-2.4A18.4,18.4,0,0,1,78.5,29.2Z" transform="translate(-21.5 -8.1)" fill="#189A57"/>
          {{-- Example: Potential MOTAC Success or Primary color --}}
          <path d="M21.5,67.1V38.3c3.6,7.3,19.7,12.1,19.7,12.1V67.6c0,12.6-18.8,13.4-19.6.8A5.7,5.7,0,0,1,21.5,67.1Z" transform="translate(-21.5 -8.1)" fill="#189A57"/>
        </g>
        {{-- Example: Potential dark overlay, consider a theme color like var(--motac-dark-text) with opacity --}}
        <path d="M78.5,48v7.6c-3.6-7.2-19.7-12-19.7-12V35h.1A21.4,21.4,0,0,1,78.5,48Z" transform="translate(-21.5 -8.1)" fill="#333C45" opacity="0.2" style="mix-blend-mode: overlay"/>
        <path d="M21.5,46.6V39c3.6,7.2,19.7,12,19.7,12v8.6h-.1A21.4,21.4,0,0,1,21.5,46.6Z" transform="translate(-21.5 -8.1)" fill="#333C45" opacity="0.2" style="mix-blend-mode: overlay"/>
        <g>
          {{-- Example: Main theme color, perhaps --motac-primary or --motac-success --}}
          <path d="M78.5,54.9V66.8a20.8,20.8,0,0,1-1.3,7.1A24.5,24.5,0,0,0,54.3,57.2H41.2A19.7,19.7,0,0,1,21.5,38.5V27.8a20.8,20.8,0,0,1,1.3-7.1,23.9,23.9,0,0,0,5.9,9.5,24.4,24.4,0,0,0,17.4,7.2H58.9A19.8,19.8,0,0,1,78.5,54.9Z" transform="translate(-21.5 -8.1)" fill="#2BC07D"/>
          <path d="M78.5,54.9V66.8a20.8,20.8,0,0,1-1.3,7.1A24.5,24.5,0,0,0,54.3,57.2H41.2A19.7,19.7,0,0,1,21.5,38.5V27.8a20.8,20.8,0,0,1,1.3-7.1,23.9,23.9,0,0,0,5.9,9.5,24.4,24.4,0,0,0,17.4,7.2H58.9A19.8,19.8,0,0,1,78.5,54.9Z" transform="translate(-21.5 -8.1)" fill="#2BC07D"/>
          {{-- Example: Highlight, consider var(--motac-surface) or a light theme accent --}}
          <path d="M78.5,54.9v2.3A20,20,0,0,0,58.6,39.5h-13A25.4,25.4,0,0,1,28,32.2c-5.2-5.1-5.4-10.9-5.2-11.5a23.9,23.9,0,0,0,5.9,9.5,24.4,24.4,0,0,0,17.4,7.2H58.9A19.8,19.8,0,0,1,78.5,54.9Z" transform="translate(-21.5 -8.1)" fill="#fff" opacity="0.5" style="mix-blend-mode: overlay"/>
          {{-- Example: Accent color, perhaps a darker shade of the main theme color --}}
          <path d="M77.2,73.9a21.7,21.7,0,0,1-5.5,7.9,20.2,20.2,0,0,1-12.9,4.7V62a4.9,4.9,0,0,0-4.5-4.8A24.5,24.5,0,0,1,77.2,73.9Z" transform="translate(-21.5 -8.1)" fill="#1CAF6B"/>
        </g>
        <g>
          <path d="M46.1,37.4a24.4,24.4,0,0,1-17.4-7.2,23.9,23.9,0,0,1-5.9-9.5,20.5,20.5,0,0,1,5.5-7.8A19.8,19.8,0,0,1,41.2,8.1V32.6A4.8,4.8,0,0,0,46.1,37.4Z" transform="translate(-21.5 -8.1)" fill="#2BC07D"/>
          <path d="M46.1,37.4a24.4,24.4,0,0,1-17.4-7.2,23.9,23.9,0,0,1-5.9-9.5c.4-.9.8-1.9,1.3-2.8a34.4,34.4,0,0,0,5.5,10.6,24.3,24.3,0,0,0,15.3,8.8h1.2Z" transform="translate(-21.5 -8.1)" fill="#333C45" opacity="0.7" style="mix-blend-mode: overlay"/>
        </g>
        <g>
          <path d="M53.9,57.2a24.4,24.4,0,0,1,17.4,7.2,23.9,23.9,0,0,1,5.9,9.5,20.5,20.5,0,0,1-5.5,7.8,19.8,19.8,0,0,1-12.9,4.8V62A4.8,4.8,0,0,0,53.9,57.2Z" transform="translate(-21.5 -8.1)" fill="#2BC07D"/>
          <path d="M53.9,57.2a24.4,24.4,0,0,1,17.4,7.2,23.9,23.9,0,0,1,5.9,9.5c-.4.9-.8,1.9-1.3,2.8a34.4,34.4,0,0,0-5.5-10.6,24.3,24.3,0,0,0-15.3-8.8H53.9Z" transform="translate(-21.5 -8.1)" fill="#333C45" opacity="0.7" style="mix-blend-mode: overlay"/>
          {{-- Example: Light fill, consider var(--motac-surface) or var(--motac-background-light-accent) --}}
          <path d="M53.9,57.2C64.3,55.9,75,63.5,77.2,73.8A28.7,28.7,0,0,0,53.9,57.2Z" transform="translate(-21.5 -8.1)" fill="#f5f6f6"/>
        </g>
        <path d="M46.1,37.4C35.7,38.7,25,31.1,22.8,20.8A28.7,28.7,0,0,0,46.1,37.4Z" transform="translate(-21.5 -8.1)" fill="#f5f6f6"/>
      </g>
    </g>
  </g>
</svg>
