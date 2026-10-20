<template>
  <div class="flex flex-wrap gap-3">
    <button
      type="button"
      class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
      :disabled="exporting"
      @click="exportExcel"
    >
      <span v-if="exporting === 'excel'" class="mr-2 animate-spin">⏳</span>
      Ekspor Excel
    </button>
    <button
      type="button"
      class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
      :disabled="exporting"
      @click="exportPdf"
    >
      <span v-if="exporting === 'pdf'" class="mr-2 animate-spin">⏳</span>
      Ekspor PDF
    </button>
  </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  rsvps: {
    type: Array,
    default: () => [],
  },
  wishes: {
    type: Array,
    default: () => [],
  },
});

const exporting = ref(null);

function buildRows() {
  return props.rsvps.map((rsvp) => {
    const wish = props.wishes.find(
      (w) => w.guestId === rsvp.guestId || w.guestName === rsvp.guestName,
    );
    return {
      "Nama Tamu": rsvp.guestName || "-",
      "Status RSVP": rsvp.status || "-",
      "Jumlah Tamu": rsvp.numberOfGuests ?? "-",
      Ucapan: wish ? wish.message : "-",
    };
  });
}

async function exportExcel() {
  exporting.value = "excel";
  try {
    const XLSX = await import("xlsx");
    const rows = buildRows();
    if (rows.length === 0) {
      rows.push({
        "Nama Tamu": "-",
        "Status RSVP": "-",
        "Jumlah Tamu": "-",
        Ucapan: "-",
      });
    }
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "RSVP");

    // Auto-size columns
    const colWidths = Object.keys(rows[0]).map((key) => ({
      wch:
        Math.max(key.length, ...rows.map((r) => String(r[key] || "").length)) +
        2,
    }));
    ws["!cols"] = colWidths;

    XLSX.writeFile(wb, "rsvp-data.xlsx");
  } catch (err) {
    console.error("Gagal mengekspor Excel:", err);
  } finally {
    exporting.value = null;
  }
}

async function exportPdf() {
  exporting.value = "pdf";
  try {
    const { jsPDF } = await import("jspdf");
    const rows = buildRows();

    const doc = new jsPDF({
      orientation: "landscape",
      unit: "mm",
      format: "a4",
    });

    doc.setFontSize(16);
    doc.text("Data RSVP", 14, 15);

    doc.setFontSize(10);
    const headers = ["Nama Tamu", "Status RSVP", "Jumlah Tamu", "Ucapan"];
    const colX = [14, 74, 134, 164];
    let y = 28;

    // Header row
    doc.setFont(undefined, "bold");
    headers.forEach((h, i) => {
      doc.text(h, colX[i], y);
    });
    doc.setFont(undefined, "normal");
    y += 2;
    doc.setDrawColor(200);
    doc.line(14, y, 283, y);
    y += 6;

    if (rows.length === 0) {
      doc.text("Belum ada data RSVP.", 14, y);
    } else {
      for (const row of rows) {
        if (y > 190) {
          doc.addPage();
          y = 15;
        }
        doc.text(String(row["Nama Tamu"]), colX[0], y);
        doc.text(String(row["Status RSVP"]), colX[1], y);
        doc.text(String(row["Jumlah Tamu"]), colX[2], y);
        // Truncate long wishes for PDF
        const wishText = String(row["Ucapan"]).substring(0, 60);
        doc.text(wishText, colX[3], y);
        y += 7;
      }
    }

    doc.save("rsvp-data.pdf");
  } catch (err) {
    console.error("Gagal mengekspor PDF:", err);
  } finally {
    exporting.value = null;
  }
}
</script>
