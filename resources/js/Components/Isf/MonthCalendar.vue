<script setup>
/**
 * MonthCalendar — static month view (Monday-first) with highlighted event days
 * and an agenda list below. No navigation. Slot "footer" for actions (e.g. Subscribe).
 */
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { formatDate } from "@/Utils/formatters";
import Table from "@/Components/UI/Table.vue";
import TableHeader from "@/Components/UI/TableHeader.vue";
import TableBody from "@/Components/UI/TableBody.vue";
import TableRow from "@/Components/UI/TableRow.vue";
import TableHead from "@/Components/UI/TableHead.vue";
import TableCell from "@/Components/UI/TableCell.vue";

const props = defineProps({
    year: {
        type: Number,
        required: true,
    },
    // 1–12
    month: {
        type: Number,
        required: true,
    },
    // Each item: { date: 'YYYY-MM-DD', title: string }
    events: {
        type: Array,
        default: () => [],
    },
    class: {
        type: String,
        default: "",
    },
});

const WEEKDAYS = [
    { short: "Sen", long: "Senin" },
    { short: "Sel", long: "Selasa" },
    { short: "Rab", long: "Rabu" },
    { short: "Kam", long: "Kamis" },
    { short: "Jum", long: "Jumat" },
    { short: "Sab", long: "Sabtu" },
    { short: "Min", long: "Minggu" },
];

const pad = (n) => String(n).padStart(2, "0");

const monthTitle = computed(() =>
    new Date(props.year, props.month - 1, 1).toLocaleDateString("id-ID", {
        month: "long",
        year: "numeric",
    }),
);

const eventsByDate = computed(() => {
    const map = {};
    props.events.forEach((event) => {
        (map[event.date] = map[event.date] || []).push(event.title);
    });
    return map;
});

const weeks = computed(() => {
    const firstWeekday =
        (new Date(props.year, props.month - 1, 1).getDay() + 6) % 7;
    const daysInMonth = new Date(props.year, props.month, 0).getDate();
    const cells = [];
    for (let i = 0; i < firstWeekday; i++) cells.push(null);
    for (let day = 1; day <= daysInMonth; day++) {
        const date = `${props.year}-${pad(props.month)}-${pad(day)}`;
        cells.push({ day, date, titles: eventsByDate.value[date] || [] });
    }
    while (cells.length % 7 !== 0) cells.push(null);
    const rows = [];
    for (let i = 0; i < cells.length; i += 7) rows.push(cells.slice(i, i + 7));
    return rows;
});

const sortedEvents = computed(() =>
    [...props.events].sort((a, b) => a.date.localeCompare(b.date)),
);

const classes = computed(() =>
    cn("isf-editorial-card isf-editorial-compact bg-white p-3", props.class),
);
</script>

<template>
    <div :class="classes">
        <p class="isf-headline text-lg font-bold capitalize text-neutral-900">
            {{ monthTitle }}
        </p>
        <Table class="mt-3 table-fixed border-y border-neutral-200 text-center">
            <caption class="sr-only">
                Kalender
                {{
                    monthTitle
                }}
            </caption>
            <TableHeader>
                <TableRow class="hover:bg-transparent">
                    <TableHead
                        v-for="weekday in WEEKDAYS"
                        :key="weekday.short"
                        scope="col"
                        class="h-8 px-0 text-center font-ui text-xs uppercase tracking-[0.06em] text-neutral-600"
                    >
                        <abbr :title="weekday.long" class="no-underline">{{
                            weekday.short
                        }}</abbr>
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow
                    v-for="(week, weekIndex) in weeks"
                    :key="weekIndex"
                    class="border-0 hover:bg-transparent"
                >
                    <TableCell
                        v-for="(cell, cellIndex) in week"
                        :key="cellIndex"
                        class="p-1"
                    >
                        <span
                            v-if="cell"
                            :class="
                                cell.titles.length
                                    ? 'bg-isf-red font-bold text-white'
                                    : 'text-neutral-800'
                            "
                            class="mx-auto flex h-8 w-8 items-center justify-center text-sm"
                        >
                            {{ cell.day }}
                            <span v-if="cell.titles.length" class="sr-only">
                                — agenda: {{ cell.titles.join(", ") }}
                            </span>
                        </span>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <ul
            v-if="sortedEvents.length"
            class="mt-4 space-y-2 border-t border-neutral-200 pt-4"
        >
            <li
                v-for="event in sortedEvents"
                :key="`${event.date}-${event.title}`"
                class="flex gap-3 text-sm"
            >
                <time
                    :datetime="event.date"
                    class="w-32 shrink-0 font-semibold text-isf-red-dark"
                >
                    {{ formatDate(event.date) }}
                </time>
                <span class="text-neutral-800">{{ event.title }}</span>
            </li>
        </ul>

        <div v-if="$slots.footer" class="mt-4">
            <slot name="footer" />
        </div>
    </div>
</template>
