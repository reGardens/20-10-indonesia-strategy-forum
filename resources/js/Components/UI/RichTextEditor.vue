<script setup>
import { useEditor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import Link from "@tiptap/extension-link";
import Image from "@tiptap/extension-image";
import Placeholder from "@tiptap/extension-placeholder";
import { TextStyle } from "@tiptap/extension-text-style";
import { Color } from "@tiptap/extension-color";
import { Highlight } from "@tiptap/extension-highlight";
import { Mark, mergeAttributes } from "@tiptap/core";
import TextAlign from "@tiptap/extension-text-align";
import { ref, watch, onBeforeUnmount, computed } from "vue";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";

// Custom Extension for Font Weight using Tailwind classes
const FontWeight = Mark.create({
    name: "fontWeight",
    addAttributes() {
        return {
            weight: {
                default: "normal",
                parseHTML: (element) => element.getAttribute("data-weight"),
                renderHTML: (attributes) => ({
                    "data-weight": attributes.weight,
                    class: `font-${attributes.weight}`,
                }),
            },
        };
    },
    parseHTML() {
        return [{ tag: "span[data-weight]" }];
    },
    renderHTML({ HTMLAttributes }) {
        return ["span", mergeAttributes(HTMLAttributes), 0];
    },
    addCommands() {
        return {
            setFontWeight:
                (weight) =>
                ({ commands }) => {
                    return commands.setMark(this.name, { weight });
                },
            unsetFontWeight:
                () =>
                ({ commands }) => {
                    return commands.unsetMark(this.name);
                },
        };
    },
});

// Custom Extension for Font Size using inline styles
const FontSize = Mark.create({
    name: "fontSize",
    addAttributes() {
        return {
            size: {
                default: null,
                parseHTML: (element) => element.style.fontSize || null,
                renderHTML: (attributes) => {
                    if (!attributes.size) {
                        return {};
                    }
                    return {
                        style: `font-size: ${attributes.size}`,
                    };
                },
            },
        };
    },
    parseHTML() {
        return [
            {
                tag: "span",
                getAttrs: (element) => {
                    return element.style.fontSize ? {} : false;
                },
            },
        ];
    },
    renderHTML({ HTMLAttributes }) {
        return ["span", mergeAttributes(HTMLAttributes), 0];
    },
    addCommands() {
        return {
            setFontSize:
                (size) =>
                ({ commands }) => {
                    return commands.setMark(this.name, { size });
                },
            unsetFontSize:
                () =>
                ({ commands }) => {
                    return commands.unsetMark(this.name);
                },
        };
    },
});

// Custom Extension for Superscript using <sup> tag
const Superscript = Mark.create({
    name: "superscript",
    parseHTML() {
        return [
            {
                tag: "sup",
            },
        ];
    },
    renderHTML({ HTMLAttributes }) {
        return ["sup", mergeAttributes(HTMLAttributes), 0];
    },
    addCommands() {
        return {
            toggleSuperscript:
                () =>
                ({ commands }) => {
                    return commands.toggleMark(this.name);
                },
        };
    },
});

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "Tulis sesuatu...",
    },
});

const emit = defineEmits(["update:modelValue"]);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        FontWeight,
        FontSize,
        Superscript,
        TextStyle,
        Color,
        Highlight.configure({ multicolor: true }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: "text-emerald-600 underline cursor-pointer",
            },
        }),
        Image.configure({
            HTMLAttributes: {
                class: "max-w-full h-auto rounded-lg border",
            },
        }),
        Placeholder.configure({
            placeholder: ({ node }) => {
                return props.placeholder;
            },
        }),
        TextAlign.configure({
            types: ["heading", "paragraph"],
        }),
    ],
    editorProps: {
        attributes: {
            class: "prose prose-sm dark:prose-invert focus:outline-none max-w-none min-h-[100px] p-3 rounded-md border border-input bg-background",
        },
    },
    onUpdate: ({ editor }) => {
        emit("update:modelValue", editor.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && editor.value.getHTML() !== value) {
            editor.value.commands.setContent(value, false);
        }
    },
);

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
});
const showColorPicker = ref(false);
const showHighlightPicker = ref(false);
const initialColor = ref("#000000");
const initialHighlight = ref("transparent");

const showFontSizePicker = ref(false);
const showSymbolPicker = ref(false);

const fontSizes = [
    "8px",
    "9px",
    "10px",
    "11px",
    "12px",
    "14px",
    "16px",
    "18px",
    "20px",
    "22px",
    "24px",
    "26px",
    "28px",
    "32px",
    "36px",
    "40px",
    "48px",
    "56px",
    "72px",
];

const symbols = [
    "®",
    "™",
    "©",
    "℠",
    "°",
    "±",
    "×",
    "÷",
    "•",
    "…",
    "→",
    "←",
    "↑",
    "↓",
    "✓",
    "✗",
    "α",
    "β",
    "γ",
    "π",
    "∑",
    "½",
    "¼",
    "¾",
    "∞",
];

const activeFontSize = computed(() => {
    if (!editor.value) return "16px";
    for (const size of fontSizes) {
        if (editor.value.isActive("fontSize", { size })) {
            return size;
        }
    }
    return editor.value.getAttributes("fontSize").size || "16px";
});

function insertSymbol(symbol) {
    if (symbol === "®" || symbol === "™") {
        editor.value
            .chain()
            .focus()
            .insertContent(`<sup>${symbol}</sup>`)
            .run();
    } else {
        editor.value.chain().focus().insertContent(symbol).run();
    }
    showSymbolPicker.value = false;
}

function setFontSize(size) {
    editor.value.chain().focus().setFontSize(size).run();
    showFontSizePicker.value = false;
}

function resetFontSize() {
    editor.value.chain().focus().unsetFontSize().run();
    showFontSizePicker.value = false;
}

function openColorPicker() {
    initialColor.value =
        editor.value.getAttributes("textStyle").color || "#000000";
    showColorPicker.value = true;
    showHighlightPicker.value = false;
}

function openHighlightPicker() {
    initialHighlight.value =
        editor.value.getAttributes("highlight").color || "transparent";
    showHighlightPicker.value = true;
    showColorPicker.value = false;
}

function cancelColor() {
    editor.value.chain().focus().setColor(initialColor.value).run();
    showColorPicker.value = false;
}

function cancelHighlight() {
    if (initialHighlight.value === "transparent") {
        editor.value.chain().focus().unsetHighlight().run();
    } else {
        editor.value
            .chain()
            .focus()
            .toggleHighlight({ color: initialHighlight.value })
            .run();
    }
    showHighlightPicker.value = false;
}

function saveSection() {
    // logic here
}

function setLink() {
    const previousUrl = editor.value.getAttributes("link").href;
    const url = window.prompt("URL", previousUrl);

    // cancelled
    if (url === null) {
        return;
    }

    // empty
    if (url === "") {
        editor.value.chain().focus().extendMarkRange("link").unsetLink().run();
        return;
    }

    // update link
    editor.value
        .chain()
        .focus()
        .extendMarkRange("link")
        .setLink({ href: url })
        .run();
}

const imageInput = ref(null);

function addImage() {
    imageInput.value?.click();
}

async function handleImageUpload(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("image", file);

    try {
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]',
        )?.content;
        const headers = {};
        if (csrfToken) {
            headers["X-CSRF-TOKEN"] = csrfToken;
        }

        const response = await fetch("/cms/upload-image", {
            method: "POST",
            headers,
            credentials: "same-origin",
            body: formData,
        });

        if (response.ok) {
            const data = await response.json();
            editor.value.chain().focus().setImage({ src: data.url }).run();
        } else {
            alert(
                "Gagal upload gambar. Pastikan format JPG/PNG/WebP/SVG dan ukuran max 2MB.",
            );
        }
    } catch (error) {
        alert("Terjadi kesalahan saat upload gambar.");
    }

    // Reset input
    event.target.value = "";
}
</script>

<template>
    <div class="rich-text-editor">
        <!-- Hidden file input for image upload -->
        <input
            ref="imageInput"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
            class="hidden"
            @change="handleImageUpload"
        />

        <!-- Toolbar -->
        <div
            v-if="editor"
            class="flex flex-wrap gap-1 p-1.5 border border-b-0 border-input rounded-t-md bg-muted/50"
        >
            <!-- Standard Formatting -->
            <button
                @click="editor.chain().focus().toggleBold().run()"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive('bold'),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Bold"
            >
                <IconComponent name="Bold" class="w-4 h-4" />
            </button>
            <button
                @click="editor.chain().focus().toggleItalic().run()"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive('italic'),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Italic"
            >
                <IconComponent name="Italic" class="w-4 h-4" />
            </button>
            <button
                @click="editor.chain().focus().toggleSuperscript().run()"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive('superscript'),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Superscript"
            >
                <IconComponent name="Superscript" class="w-4 h-4" />
            </button>

            <div class="w-px h-4 bg-border mx-1 self-center"></div>

            <!-- Custom Font Weights -->
            <div
                class="flex items-center gap-0.5 bg-background/50 rounded-md p-0.5 border"
            >
                <button
                    v-for="w in ['light', 'medium', 'bold']"
                    :key="w"
                    @click="editor.chain().focus().setFontWeight(w).run()"
                    :class="{
                        'bg-emerald-600 text-white': editor.isActive(
                            'fontWeight',
                            { weight: w },
                        ),
                    }"
                    class="px-2 py-1 text-[9px] uppercase font-bold rounded transition-all hover:bg-emerald-100 hover:text-emerald-800"
                    type="button"
                >
                    {{ w }}
                </button>
                <button
                    @click="editor.chain().focus().unsetFontWeight().run()"
                    class="px-2 py-1 text-[9px] uppercase font-bold rounded hover:bg-red-50 text-red-500"
                    type="button"
                    title="Reset Weight"
                >
                    X
                </button>
            </div>

            <div class="w-px h-4 bg-border mx-1 self-center"></div>

            <!-- Font Size Picker Popover -->
            <div class="relative">
                <button
                    @click="
                        showFontSizePicker = !showFontSizePicker;
                        showColorPicker = false;
                        showHighlightPicker = false;
                        showSymbolPicker = false;
                    "
                    class="p-1 px-2 rounded hover:bg-accent transition-all flex items-center gap-1 border h-[28px]"
                    :class="{ 'bg-accent shadow-sm': showFontSizePicker }"
                    type="button"
                    title="Font Size"
                >
                    <IconComponent name="TextSize" class="w-3.5 h-3.5" />
                    <span
                        class="text-[10px] font-semibold text-muted-foreground min-w-[28px] text-center"
                    >
                        {{ activeFontSize }}
                    </span>
                </button>

                <!-- Click Outside Backdrop -->
                <div
                    v-if="showFontSizePicker"
                    @click="showFontSizePicker = false"
                    class="fixed inset-0 z-40 bg-transparent"
                ></div>

                <!-- Font Size Popover Content -->
                <div
                    v-if="showFontSizePicker"
                    class="absolute top-full left-0 mt-1 z-50 bg-popover border rounded-xl shadow-2xl p-2 w-[110px] animate-in fade-in zoom-in duration-200"
                >
                    <!-- Scrollable Container -->
                    <div
                        class="max-h-56 overflow-y-auto space-y-0.5 custom-scrollbar"
                    >
                        <button
                            v-for="size in fontSizes"
                            :key="size"
                            @click="setFontSize(size)"
                            class="w-full text-left px-2 py-1.5 rounded text-[11px] font-medium hover:bg-accent transition-colors flex items-center justify-between"
                            :class="{
                                'bg-emerald-50 text-emerald-700 font-bold':
                                    editor.isActive('fontSize', { size }),
                            }"
                            type="button"
                        >
                            <span>{{ size }}</span>
                            <span
                                v-if="editor.isActive('fontSize', { size })"
                                class="text-[9px]"
                                >✓</span
                            >
                        </button>
                    </div>
                    <div class="border-t mt-1.5 pt-1.5">
                        <button
                            @click="resetFontSize"
                            class="w-full px-2 py-1 text-[10px] uppercase font-bold text-center rounded bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 transition-colors"
                            type="button"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="w-px h-4 bg-border mx-1 self-center"></div>

            <!-- Lists -->
            <button
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive('bulletList'),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Bullet List"
            >
                <IconComponent name="List" class="w-4 h-4" />
            </button>
            <button
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive('orderedList'),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Ordered List"
            >
                <IconComponent name="ListOrdered" class="w-4 h-4" />
            </button>

            <div class="w-px h-4 bg-border mx-1 self-center"></div>

            <!-- Alignment -->
            <button
                @click="editor.chain().focus().setTextAlign('left').run()"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive({ textAlign: 'left' }),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Align Left"
            >
                <IconComponent name="AlignLeft" class="w-4 h-4" />
            </button>
            <button
                @click="editor.chain().focus().setTextAlign('center').run()"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive({ textAlign: 'center' }),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Align Center"
            >
                <IconComponent name="AlignCenter" class="w-4 h-4" />
            </button>
            <button
                @click="editor.chain().focus().setTextAlign('right').run()"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive({ textAlign: 'right' }),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Align Right"
            >
                <IconComponent name="AlignRight" class="w-4 h-4" />
            </button>
            <button
                @click="editor.chain().focus().setTextAlign('justify').run()"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive({ textAlign: 'justify' }),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Align Justify"
            >
                <IconComponent name="AlignJustify" class="w-4 h-4" />
            </button>

            <div class="w-px h-4 bg-border mx-1 self-center"></div>

            <!-- Links & Media -->
            <button
                @click="setLink"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive('link'),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Link"
            >
                <IconComponent name="Link" class="w-4 h-4" />
            </button>
            <button
                @click="addImage"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Image"
            >
                <IconComponent name="Image" class="w-4 h-4" />
            </button>

            <!-- Symbol Picker Popover -->
            <div class="relative">
                <button
                    @click="
                        showSymbolPicker = !showSymbolPicker;
                        showColorPicker = false;
                        showHighlightPicker = false;
                        showFontSizePicker = false;
                    "
                    class="p-1.5 rounded hover:bg-accent transition-all flex items-center gap-1 border h-[28px]"
                    :class="{ 'bg-accent shadow-sm': showSymbolPicker }"
                    type="button"
                    title="Insert Symbol"
                >
                    <IconComponent name="Registered" class="w-3.5 h-3.5" />
                    <span
                        class="text-[10px] font-semibold text-muted-foreground"
                        >Symbol</span
                    >
                </button>

                <!-- Click Outside Backdrop -->
                <div
                    v-if="showSymbolPicker"
                    @click="showSymbolPicker = false"
                    class="fixed inset-0 z-40 bg-transparent"
                ></div>

                <!-- Symbol Popover Content -->
                <div
                    v-if="showSymbolPicker"
                    class="absolute top-full left-0 mt-1 z-50 bg-popover border rounded-xl shadow-2xl p-3 min-w-[200px] animate-in fade-in zoom-in duration-200"
                >
                    <p
                        class="text-[10px] text-muted-foreground mb-2 font-semibold uppercase tracking-wider"
                    >
                        Insert Symbol
                    </p>
                    <!-- Scrollable Grid -->
                    <div
                        class="grid grid-cols-5 gap-1.5 max-h-48 overflow-y-auto pr-1 custom-scrollbar"
                    >
                        <button
                            v-for="sym in symbols"
                            :key="sym"
                            @click="insertSymbol(sym)"
                            class="w-7 h-7 rounded border border-muted hover:bg-accent hover:border-accent-foreground text-sm font-semibold flex items-center justify-center transition-all active:scale-95 bg-background"
                            type="button"
                        >
                            {{ sym }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="w-px h-4 bg-border mx-1 self-center"></div>

            <!-- Structure -->
            <button
                @click="editor.chain().focus().toggleBlockquote().run()"
                :class="{
                    'bg-accent text-accent-foreground shadow-sm':
                        editor.isActive('blockquote'),
                }"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Blockquote"
            >
                <IconComponent name="Quote" class="w-4 h-4" />
            </button>
            <button
                @click="editor.chain().focus().setHorizontalRule().run()"
                class="p-1.5 rounded hover:bg-accent transition-all"
                type="button"
                title="Horizontal Rule"
            >
                <IconComponent name="Minus" class="w-4 h-4" />
            </button>

            <div class="w-px h-4 bg-border mx-1 self-center"></div>

            <!-- Color Picker Popover -->
            <div class="relative">
                <button
                    @click="openColorPicker"
                    class="p-1.5 rounded hover:bg-accent transition-all flex items-center gap-1 border"
                    :class="{ 'bg-accent shadow-sm': showColorPicker }"
                    type="button"
                    title="Text Color"
                >
                    <IconComponent name="Palette" class="w-4 h-4" />
                    <div
                        class="w-2 h-2 rounded-full border border-white"
                        :style="{
                            backgroundColor:
                                editor.getAttributes('textStyle').color ||
                                '#000000',
                        }"
                    ></div>
                </button>

                <!-- Click Outside Backdrop -->
                <div
                    v-if="showColorPicker"
                    @click="showColorPicker = false"
                    class="fixed inset-0 z-40 bg-transparent"
                ></div>

                <!-- Color Popover Content -->
                <div
                    v-if="showColorPicker"
                    class="absolute top-full left-0 mt-1 z-50 bg-popover border rounded-xl shadow-2xl p-3 min-w-[220px] animate-in fade-in zoom-in duration-200"
                >
                    <div class="space-y-3">
                        <!-- Current Color Preview & Hex Input -->
                        <div class="flex items-center gap-2 pb-2 border-b">
                            <div
                                class="w-8 h-8 rounded-lg border shadow-sm"
                                :style="{
                                    backgroundColor:
                                        editor.getAttributes('textStyle')
                                            .color || '#000000',
                                }"
                            ></div>
                            <div class="flex-1">
                                <input
                                    type="text"
                                    :value="
                                        editor.getAttributes('textStyle')
                                            .color || '#000000'
                                    "
                                    @change="
                                        editor
                                            .chain()
                                            .focus()
                                            .setColor($event.target.value)
                                            .run()
                                    "
                                    class="w-full text-[10px] font-mono p-1 border rounded bg-muted uppercase"
                                    placeholder="#000000"
                                />
                            </div>
                        </div>

                        <!-- Presets Grid -->
                        <div>
                            <p
                                class="text-[10px] text-muted-foreground mb-2 font-semibold uppercase tracking-wider"
                            >
                                Presets
                            </p>
                            <div class="grid grid-cols-5 gap-1.5">
                                <button
                                    v-for="color in [
                                        '#1E4E8C',
                                        '#2559A3',
                                        '#3AB0E5',
                                        '#10b981',
                                        '#ef4444',
                                        '#f59e0b',
                                        '#6366f1',
                                        '#a855f7',
                                        '#000000',
                                        '#ffffff',
                                    ]"
                                    :key="color"
                                    @click="
                                        editor
                                            .chain()
                                            .focus()
                                            .setColor(color)
                                            .run()
                                    "
                                    class="w-7 h-7 rounded-md border border-muted shadow-sm hover:scale-110 transition-transform active:scale-95"
                                    :style="{ backgroundColor: color }"
                                    type="button"
                                ></button>
                            </div>
                        </div>

                        <!-- Custom & Reset -->
                        <div class="flex items-center gap-2 pt-2 border-t">
                            <label
                                class="flex-1 flex items-center justify-center gap-2 px-2 py-1.5 bg-secondary text-secondary-foreground rounded-md text-[10px] font-bold cursor-pointer hover:bg-secondary/80 transition-colors border"
                            >
                                <IconComponent name="Palette" class="w-3 h-3" />
                                CUSTOM
                                <input
                                    type="color"
                                    @input="
                                        editor
                                            .chain()
                                            .focus()
                                            .setColor($event.target.value)
                                            .run()
                                    "
                                    class="sr-only"
                                />
                            </label>
                            <button
                                @click="
                                    editor.chain().focus().unsetColor().run();
                                    showColorPicker = false;
                                "
                                class="px-3 py-1.5 text-[10px] uppercase font-bold rounded bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 transition-colors"
                                type="button"
                                title="Hapus Warna & Kembali ke Default"
                            >
                                Reset
                            </button>
                        </div>

                        <!-- Footer: Apply & Cancel -->
                        <div class="flex items-center gap-2 pt-2 border-t mt-2">
                            <button
                                @click="showColorPicker = false"
                                class="flex-1 px-2 py-1.5 bg-emerald-600 text-white text-[10px] uppercase font-bold rounded hover:bg-emerald-700 transition-colors"
                                type="button"
                            >
                                Apply
                            </button>
                            <button
                                @click="cancelColor"
                                class="flex-1 px-2 py-1.5 bg-muted text-muted-foreground text-[10px] uppercase font-bold rounded hover:bg-muted/80 transition-colors border"
                                type="button"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Highlight Picker Popover -->
            <div class="relative">
                <button
                    @click="openHighlightPicker"
                    class="p-1.5 rounded hover:bg-accent transition-all flex items-center gap-1 border"
                    :class="{ 'bg-accent shadow-sm': showHighlightPicker }"
                    type="button"
                    title="Highlight Color"
                >
                    <IconComponent name="Highlighter" class="w-4 h-4" />
                    <div
                        class="w-2 h-2 rounded border border-white"
                        :style="{
                            backgroundColor:
                                editor.getAttributes('highlight').color ||
                                'transparent',
                        }"
                    ></div>
                </button>

                <!-- Click Outside Backdrop -->
                <div
                    v-if="showHighlightPicker"
                    @click="showHighlightPicker = false"
                    class="fixed inset-0 z-40 bg-transparent"
                ></div>

                <!-- Highlight Popover Content -->
                <div
                    v-if="showHighlightPicker"
                    class="absolute top-full left-0 mt-1 z-50 bg-popover border rounded-xl shadow-2xl p-3 min-w-[220px] animate-in fade-in zoom-in duration-200"
                >
                    <div class="space-y-3">
                        <!-- Current Highlight Preview & Hex Input -->
                        <div class="flex items-center gap-2 pb-2 border-b">
                            <div
                                class="w-8 h-8 rounded-lg border shadow-sm"
                                :style="{
                                    backgroundColor:
                                        editor.getAttributes('highlight')
                                            .color || 'transparent',
                                }"
                            ></div>
                            <div class="flex-1">
                                <input
                                    type="text"
                                    :value="
                                        editor.getAttributes('highlight')
                                            .color || '#ffffff'
                                    "
                                    @change="
                                        editor
                                            .chain()
                                            .focus()
                                            .toggleHighlight({
                                                color: $event.target.value,
                                            })
                                            .run()
                                    "
                                    class="w-full text-[10px] font-mono p-1 border rounded bg-muted uppercase"
                                    placeholder="#ffffff"
                                />
                            </div>
                        </div>

                        <!-- Presets Grid -->
                        <div>
                            <p
                                class="text-[10px] text-muted-foreground mb-2 font-semibold uppercase tracking-wider"
                            >
                                Presets
                            </p>
                            <div class="grid grid-cols-5 gap-1.5">
                                <button
                                    v-for="color in [
                                        '#3CB4E5',
                                        '#fef08a',
                                        '#bfdbfe',
                                        '#bbf7d0',
                                        '#fecaca',
                                        '#e9d5ff',
                                        '#fed7aa',
                                        '#99f6e4',
                                        '#e2e8f0',
                                        '#ffffff',
                                    ]"
                                    :key="color"
                                    @click="
                                        editor
                                            .chain()
                                            .focus()
                                            .toggleHighlight({ color })
                                            .run()
                                    "
                                    class="w-7 h-7 rounded-md border border-muted shadow-sm hover:scale-110 transition-transform active:scale-95"
                                    :style="{ backgroundColor: color }"
                                    type="button"
                                ></button>
                            </div>
                        </div>

                        <!-- Custom & Reset -->
                        <div class="flex items-center gap-2 pt-2 border-t">
                            <label
                                class="flex-1 flex items-center justify-center gap-2 px-2 py-1.5 bg-secondary text-secondary-foreground rounded-md text-[10px] font-bold cursor-pointer hover:bg-secondary/80 transition-colors border"
                            >
                                <IconComponent name="Palette" class="w-3 h-3" />
                                CUSTOM
                                <input
                                    type="color"
                                    @input="
                                        editor
                                            .chain()
                                            .focus()
                                            .toggleHighlight({
                                                color: $event.target.value,
                                            })
                                            .run()
                                    "
                                    class="sr-only"
                                />
                            </label>
                            <button
                                @click="
                                    editor
                                        .chain()
                                        .focus()
                                        .unsetHighlight()
                                        .run()
                                "
                                class="px-2 py-1.5 text-[10px] uppercase font-bold rounded hover:bg-red-50 text-red-500 border border-red-100 transition-colors"
                                type="button"
                            >
                                Reset
                            </button>
                        </div>

                        <!-- Footer: Apply & Cancel -->
                        <div class="flex items-center gap-2 pt-2 border-t mt-2">
                            <button
                                @click="showHighlightPicker = false"
                                class="flex-1 px-2 py-1.5 bg-emerald-600 text-white text-[10px] uppercase font-bold rounded hover:bg-emerald-700 transition-colors"
                                type="button"
                            >
                                Apply
                            </button>
                            <button
                                @click="cancelHighlight"
                                class="flex-1 px-2 py-1.5 bg-muted text-muted-foreground text-[10px] uppercase font-bold rounded hover:bg-muted/80 transition-colors border"
                                type="button"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1"></div>

            <!-- History -->
            <button
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().undo()"
                class="p-1.5 rounded hover:bg-accent transition-all disabled:opacity-30"
                type="button"
                title="Undo"
            >
                <IconComponent name="Undo" class="w-4 h-4" />
            </button>
            <button
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().redo()"
                class="p-1.5 rounded hover:bg-accent transition-all disabled:opacity-30"
                type="button"
                title="Redo"
            >
                <IconComponent name="Redo" class="w-4 h-4" />
            </button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>

<style>
/* Basic Tiptap Styles */
.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
}

/* Ensure formatting is visible despite Tailwind reset */
.ProseMirror strong,
.ProseMirror b {
    font-weight: bold !important;
}

.ProseMirror .font-light {
    font-weight: 300 !important;
}

.ProseMirror .font-medium {
    font-weight: 500 !important;
}

.ProseMirror .font-bold {
    font-weight: 700 !important;
}

.ProseMirror em,
.ProseMirror i {
    font-style: italic !important;
}

.ProseMirror h2 {
    font-size: 1.5rem !important;
    font-weight: bold !important;
    margin-top: 1rem !important;
    margin-bottom: 0.5rem !important;
}

.ProseMirror h3 {
    font-size: 1.25rem !important;
    font-weight: bold !important;
    margin-top: 0.75rem !important;
    margin-bottom: 0.5rem !important;
}

.ProseMirror p {
    margin-bottom: 0.5rem !important;
}

.ProseMirror sup {
    vertical-align: baseline !important;
    position: relative !important;
    top: -0.45em !important;
    font-size: 70% !important;
    line-height: 0 !important;
}

/* Ensure background color highlights span the full height when superscript is highlighted */
.ProseMirror sup mark,
.ProseMirror sup span[style*="background-color"],
.ProseMirror mark sup,
.ProseMirror span[style*="background-color"] sup {
    padding-bottom: 0.35em !important;
    padding-top: 0.1em !important;
}

.ProseMirror:focus {
    outline: none !important;
}

/* Scrollbar styling for popovers */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.2);
    border-radius: 2px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(100, 116, 139, 0.4);
}
</style>
