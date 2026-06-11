import qz from 'qz-tray';

export interface TicketItem {
    nombre: string;
    precio: number;
}

export interface TicketData {
    clinicaNombre: string;
    clinicaRuc: string;
    clinicaTel: string;
    fecha: string;
    pacienteNombre: string;
    pacienteDni: string;
    items: TicketItem[];
    total: number;
    metodoPago: string;
    mensajePie: string;
}

export class QZTrayService {
    private static connected = false;

    // Connect without certificate for local development
    static async connect() {
        if (this.connected) return;
        
        try {
            await qz.websocket.connect();
            this.connected = true;
            console.log("QZ Tray Connected");
        } catch (e) {
            console.error("Failed to connect to QZ Tray", e);
            throw e;
        }
    }

    // Get list of connected printers
    static async getPrinters(): Promise<string[]> {
        await this.connect();
        return await qz.printers.find();
    }

    // Print thermal ESC/POS ticket
    static async imprimirTicket(impresora: string, datos: TicketData) {
        await this.connect();
        
        const config = qz.configs.create(impresora);
        const escData = [
            '\x1B\x40',                    // Init
            '\x1B\x61\x01',                // Center align
            `${datos.clinicaNombre}\n`,
            `RUC: ${datos.clinicaRuc}\n`,
            `Tel: ${datos.clinicaTel}\n`,
            `${datos.fecha}\n`,
            '================================\n',
            '\x1B\x61\x00',                // Left align
            `Paciente: ${datos.pacienteNombre}\n`,
            `DNI: ${datos.pacienteDni}\n`,
            '--------------------------------\n',
            ...datos.items.map(i => `${i.nombre.padEnd(20)} S/ ${i.precio.toFixed(2)}\n`),
            '================================\n',
            '\x1B\x45\x01',                // Bold ON
            `TOTAL: S/ ${datos.total.toFixed(2)}\n`,
            '\x1B\x45\x00',                // Bold OFF
            `Metodo: ${datos.metodoPago}\n`,
            '\x1B\x61\x01',                // Center align
            `${datos.mensajePie}\n`,
            '\x1B\x64\x05',                // Feed 5 lines
            '\x1D\x56\x41',                // Cut paper
        ];
        
        try {
            await qz.print(config, escData);
            console.log("Print job sent to", impresora);
        } catch (e) {
            console.error("Print failed", e);
            throw e;
        }
    }

    static isConnected(): boolean {
        return qz.websocket.isActive();
    }
}
