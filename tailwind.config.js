import animate from "tailwindcss-animate";

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: "class",
    safelist: [
        // Classes generated dynamically by RichTextEditor (stored in DB)
        "font-light",
        "font-medium",
        "font-bold",
    ],
    content: [
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
        "./resources/js/**/*.js",
    ],
    theme: {
        container: {
            center: true,
            padding: "2rem",
            screens: {
                "2xl": "1400px",
            },
        },
        extend: {
            fontFamily: {
                sans: [
                    "Montserrat",
                    "ui-sans-serif",
                    "system-ui",
                    "sans-serif",
                ],
                montserrat: ["Montserrat", "sans-serif"],
                // Tipografi editorial ISF (Gaya Monocle / Antiqua):
                // Script / Tulisan Sambung Latin Klasik:
                script: ["'Pinyon Script'", "cursive"],
                // Masthead / Wordmark koran klasik:
                masthead: [
                    "Playfair Display",
                    "Newsreader",
                    "Plantin",
                    "Georgia",
                    "serif",
                ],
                // Headline & Serif: Antiqua / Plantin (Newsreader / Georgia)
                // Body & UI: Neo-Grotesque (Inter / Helvetica Neue)
                headline: [
                    "Newsreader",
                    "Plantin",
                    "Georgia",
                    "serif",
                ],
                serif: [
                    "Newsreader",
                    "Plantin",
                    "Georgia",
                    "serif",
                ],
                body: [
                    "Inter",
                    "-apple-system",
                    "BlinkMacSystemFont",
                    "Helvetica Neue",
                    "Arial",
                    "sans-serif",
                ],
                ui: [
                    "Inter",
                    "-apple-system",
                    "BlinkMacSystemFont",
                    "Helvetica Neue",
                    "Barlow",
                    "sans-serif",
                ],
            },
            colors: {
                // ISF brand identity (from the ISF logo)
                isf: {
                    navy: "#0B1220",
                    panel: "#111A2E",
                    red: "#E10600",
                    "red-dark": "#B30500",
                    silver: "#D1D5DB",
                    white: "#F9FAFB",
                    gray: "#6B7280",
                    amber: "#F59E0B",
                },
                border: "hsl(var(--border))",
                input: "hsl(var(--input))",
                ring: "hsl(var(--ring))",
                background: "hsl(var(--background))",
                foreground: "hsl(var(--foreground))",
                primary: {
                    DEFAULT: "hsl(var(--primary))",
                    foreground: "hsl(var(--primary-foreground))",
                },
                secondary: {
                    DEFAULT: "hsl(var(--secondary))",
                    foreground: "hsl(var(--secondary-foreground))",
                },
                destructive: {
                    DEFAULT: "hsl(var(--destructive))",
                    foreground: "hsl(var(--destructive-foreground))",
                },
                muted: {
                    DEFAULT: "hsl(var(--muted))",
                    foreground: "hsl(var(--muted-foreground))",
                },
                accent: {
                    DEFAULT: "hsl(var(--accent))",
                    foreground: "hsl(var(--accent-foreground))",
                },
                popover: {
                    DEFAULT: "hsl(var(--popover))",
                    foreground: "hsl(var(--popover-foreground))",
                },
                card: {
                    DEFAULT: "hsl(var(--card))",
                    foreground: "hsl(var(--card-foreground))",
                },
            },
            borderRadius: {
                lg: "var(--radius)",
                md: "calc(var(--radius) - 2px)",
                sm: "calc(var(--radius) - 4px)",
            },
            keyframes: {
                "accordion-down": {
                    from: { height: "0" },
                    to: { height: "var(--radix-accordion-content-height)" },
                },
                "accordion-up": {
                    from: { height: "var(--radix-accordion-content-height)" },
                    to: { height: "0" },
                },
            },
            animation: {
                "accordion-down": "accordion-down 0.2s ease-out",
                "accordion-up": "accordion-up 0.2s ease-out",
            },
        },
    },
    plugins: [animate],
};
