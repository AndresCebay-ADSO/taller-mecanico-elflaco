"""
Genera la plantilla de carga inicial del taller: plantilla_carga_inicial.xlsx (misma carpeta).

    python resources/templates/generar_plantilla.py

Las columnas deben coincidir con App\\Services\\DataImport\\ImportSchema. Si cambias una, cambia la otra.
Las hojas que se importan van vacías: los ejemplos están solo en «Instrucciones», para que ningún dato
de muestra termine en la base.
"""
from pathlib import Path

from openpyxl import Workbook
from openpyxl.comments import Comment
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.worksheet.datavalidation import DataValidation

OUT = Path(__file__).with_name("plantilla_carga_inicial.xlsx")
ROWS = 500  # filas con listas desplegables y formatos

BLUE = "3641F5"
FONT = "Arial"
HEADER_FONT = Font(name=FONT, bold=True, color="FFFFFF")
HEADER_FILL = PatternFill("solid", fgColor=BLUE)
INPUT_FILL = PatternFill("solid", fgColor="FFFBEA")  # amarillo muy suave: celdas para llenar
BODY_FONT = Font(name=FONT)
THIN = Side(style="thin", color="D0D5DD")

MONEY = '"$"#,##0'
TEXT = "@"
DATE = "DD/MM/YYYY"

# (encabezado, ancho, formato, comentario). Un * marca obligatoria.
SHEETS = {
    "Categorías": [
        ("Categoría *", 32, TEXT, "Escriba aquí las categorías con las que ordena sus productos (Aceites, Llantas, Frenos…). Una por fila."),
    ],
    "Proveedores": [
        ("Nombre *", 32, TEXT, "Máximo 30 caracteres. Debe ser único."),
        ("Teléfono *", 16, TEXT, "10 dígitos, sin espacios ni puntos. Ej.: 3101234567"),
        ("Correo", 32, TEXT, "Opcional."),
        ("Dirección", 40, TEXT, "Opcional. Máximo 50 caracteres."),
    ],
    "Productos": [
        ("Nombre *", 42, TEXT, "Como lo busca en el mostrador. Ej.: Filtro de aceite Pulsar 180. No se puede repetir."),
        ("Categoría *", 20, TEXT, "Elija de la lista (hoja Categorías) o escriba una nueva."),
        ("Código", 18, TEXT, "Opcional: código de barras o referencia del proveedor."),
        ("Proveedor *", 28, TEXT, "Elija de la lista (hoja Proveedores). Si no está, agréguelo primero allá."),
        ("Costo unitario *", 16, MONEY, "Lo que le cuesta cada unidad, en pesos, sin puntos ni signo. Ej.: 9400"),
        ("Precio de venta *", 16, MONEY, "A cuánto lo vende, en pesos. Ej.: 17000"),
        ("Stock actual", 13, "0", "Unidades que tiene hoy. Vacío = 0 (se carga después con compras)."),
        ("Stock mínimo", 13, "0", "Al llegar aquí el sistema avisa «stock bajo». Vacío = 0."),
    ],
    "Mecánicos": [
        ("Nombre *", 32, TEXT, "Máximo 30 caracteres."),
        ("Teléfono *", 16, TEXT, "10 dígitos, sin espacios. Ej.: 3101234567"),
        ("Fecha de ingreso", 16, DATE, "Opcional. Vacío = hoy."),
        ("Correo", 32, TEXT, "Opcional."),
    ],
    "Tipos de trabajo": [
        ("Nombre *", 30, TEXT, "Ej.: Cambio de aceite. No se puede repetir."),
        ("Descripción *", 40, TEXT, "Qué incluye el servicio."),
        ("Cálculo *", 16, TEXT, "Porcentaje: el mecánico gana un % de la mano de obra. Monto fijo: precio único repartido."),
        ("% mecánico", 13, "0", "Solo si el cálculo es Porcentaje. Ej.: 60 (el taller se queda con el resto)."),
        ("Monto mecánico", 16, MONEY, "Solo si el cálculo es Monto fijo."),
        ("Monto taller", 16, MONEY, "Solo si el cálculo es Monto fijo."),
        ("¿Usa repuestos?", 15, TEXT, "Sí o No. Vacío = Sí."),
    ],
}

EXAMPLES = {
    "Proveedores": ["Distrimotos Neiva", "3101234567", "ventas@distrimotos.co", "Cra. 5 # 12-34, Neiva"],
    "Productos": ["Filtro de aceite Pulsar 180", "Filtros", "7702001000141", "Distrimotos Neiva", "9400", "17000", "12", "4"],
    "Mecánicos": ["Juan Pérez", "3209876543", "15/01/2024", ""],
    "Tipos de trabajo": ["Cambio de aceite", "Cambio de aceite y revisión de niveles", "Porcentaje", "60", "", "", "Sí"],
}


def style_header(ws, columns):
    for col, (title, width, fmt, comment) in enumerate(columns, start=1):
        cell = ws.cell(row=1, column=col, value=title)
        cell.font = HEADER_FONT
        cell.fill = HEADER_FILL
        cell.alignment = Alignment(vertical="center", wrap_text=True)
        cell.comment = Comment(comment, "Taller")
        letter = cell.column_letter
        ws.column_dimensions[letter].width = width
        for row in range(2, ROWS + 2):
            body = ws.cell(row=row, column=col)
            body.number_format = fmt
            body.font = BODY_FONT
            body.fill = INPUT_FILL
            body.border = Border(bottom=THIN)
    ws.row_dimensions[1].height = 30
    ws.freeze_panes = "A2"


def add_list(ws, column_letter, source, *, strict, title, prompt):
    dv = DataValidation(type="list", formula1=source, allow_blank=True)
    dv.errorStyle = "stop" if strict else "warning"
    dv.errorTitle = title
    dv.error = prompt
    dv.showErrorMessage = True
    ws.add_data_validation(dv)
    dv.add(f"{column_letter}2:{column_letter}{ROWS + 1}")


def build():
    wb = Workbook()
    intro = wb.active
    intro.title = "Instrucciones"

    for name, columns in SHEETS.items():
        style_header(wb.create_sheet(name), columns)

    # Listas fijas en una hoja oculta.
    lists = wb.create_sheet("Listas")
    for row, value in enumerate(["Sí", "No"], start=1):
        lists.cell(row=row, column=1, value=value)
    for row, value in enumerate(["Porcentaje", "Monto fijo"], start=1):
        lists.cell(row=row, column=2, value=value)
    lists.sheet_state = "hidden"

    products = wb["Productos"]
    add_list(products, "B", f"'Categorías'!$A$2:$A${ROWS + 1}", strict=False,
             title="Categoría nueva", prompt="No está en la hoja Categorías. Se creará tal como la escribió.")
    add_list(products, "D", f"'Proveedores'!$A$2:$A${ROWS + 1}", strict=False,
             title="Proveedor", prompt="No está en la hoja Proveedores. Agréguelo allá o la importación lo marcará como error.")

    job_types = wb["Tipos de trabajo"]
    add_list(job_types, "C", "Listas!$B$1:$B$2", strict=True, title="Cálculo", prompt="Elija Porcentaje o Monto fijo.")
    add_list(job_types, "G", "Listas!$A$1:$A$2", strict=True, title="Sí o No", prompt="Elija Sí o No.")

    write_instructions(intro)
    wb.save(OUT)
    print(f"Plantilla generada: {OUT}")


def write_instructions(ws):
    ws.column_dimensions["A"].width = 4
    for letter in "BCDEFGHI":
        ws.column_dimensions[letter].width = 22
    ws.sheet_view.showGridLines = False

    def put(row, text, *, size=11, bold=False, color="101828", col=2):
        cell = ws.cell(row=row, column=col, value=text)
        cell.font = Font(name=FONT, size=size, bold=bold, color=color)
        cell.alignment = Alignment(wrap_text=False, vertical="top")
        return cell

    put(2, "Plantilla de carga inicial — MotoTaller", size=16, bold=True, color=BLUE)
    put(3, "Llene las hojas con los datos reales del taller y entregue el archivo para importarlo.", color="475467")

    steps = [
        "1. Llene en este orden: Categorías → Proveedores → Productos → Mecánicos → Tipos de trabajo.",
        "2. Las columnas con * son obligatorias. Las celdas amarillas son para llenar; no cambie los encabezados.",
        "3. Precios en pesos, sin puntos ni signo: 17000 (no 17.000). Teléfonos de 10 dígitos sin espacios.",
        "4. En Productos, la Categoría y el Proveedor se eligen de las listas. Si un proveedor no aparece, agréguelo primero en su hoja.",
        "5. «Stock actual» es lo que hay hoy en el taller. Si lo deja vacío, el producto entra en 0 y se carga con compras.",
        "6. No hace falta llenar todo de una vez: lo que ya exista en el sistema (mismo nombre) se omite al importar.",
        "7. Pase el mouse por cada encabezado para ver qué va en esa columna.",
    ]
    for i, text in enumerate(steps):
        put(5 + i, text)

    row = 5 + len(steps) + 1
    put(row, "Ejemplos de cómo se ve una fila bien llenada (no los copie: son solo guía)", size=12, bold=True, color=BLUE)
    row += 1
    for sheet, values in EXAMPLES.items():
        put(row, sheet, bold=True)
        row += 1
        for col, (title, *_rest) in enumerate(SHEETS[sheet], start=2):
            header = ws.cell(row=row, column=col, value=title)
            header.font = Font(name=FONT, bold=True, color="FFFFFF", size=9)
            header.fill = HEADER_FILL
            example = ws.cell(row=row + 1, column=col, value=values[col - 2])
            example.font = Font(name=FONT, size=9, color="475467")
            example.border = Border(bottom=THIN)
        row += 3


if __name__ == "__main__":
    build()
