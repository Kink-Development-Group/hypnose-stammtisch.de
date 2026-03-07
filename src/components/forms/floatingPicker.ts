type FloatingPickerOptions = {
  estimatedHeight: number;
  minWidth: number;
  margin?: number;
};

export function getFloatingPickerStyle(
  anchor: HTMLElement,
  options: FloatingPickerOptions,
): string {
  const margin = options.margin ?? 8;
  const rect = anchor.getBoundingClientRect();
  const availableHeight = Math.min(
    options.estimatedHeight,
    window.innerHeight - margin * 2,
  );
  const minWidth = Math.max(rect.width, options.minWidth);
  const left = Math.max(
    margin,
    Math.min(rect.left, window.innerWidth - minWidth - margin),
  );
  const spaceBelow = window.innerHeight - rect.bottom - margin;
  const spaceAbove = rect.top - margin;
  const shouldPlaceAbove =
    spaceBelow < availableHeight && spaceAbove > spaceBelow;
  const top = shouldPlaceAbove
    ? Math.max(margin, rect.top - availableHeight)
    : Math.max(
        margin,
        Math.min(
          rect.bottom + margin,
          window.innerHeight - availableHeight - margin,
        ),
      );

  return `top:${top}px;left:${left}px;min-width:${minWidth}px;max-width:calc(100vw - ${margin * 2}px);max-height:calc(100vh - ${margin * 2}px);overflow-y:auto;`;
}
