import type { Page } from "@inertiajs/core"
import type { CSSProperties } from "react"
import { Toaster as Sonner, type ToasterProps } from "sonner"

import { useAppearance } from "@/hooks/use-appearance"
import { useFlashToast } from "@/hooks/use-flash-toast"

function Toaster({ initialPage, ...props }: ToasterProps & { initialPage: Page }) {
  // The toast must match the page's resolved theme, not the raw
  // preference: 'system' is resolved by the page's .dark class, and a
  // mismatch paints dark text on a light toast (or the reverse) until
  // hydration catches up.
  const { resolvedAppearance } = useAppearance()

  useFlashToast(initialPage)

  return (
    <Sonner
      theme={resolvedAppearance}
      className="toaster group"
      position="bottom-right"
      closeButton
      style={
        {
          "--normal-bg": "var(--popover)",
          "--normal-text": "var(--popover-foreground)",
          "--normal-border": "var(--border)",
        } as CSSProperties
      }
      {...props}
    />
  )
}

export { Toaster }
