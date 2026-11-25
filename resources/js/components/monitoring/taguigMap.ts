import { levelColors, type SchoolRow } from '@/types/monitoring';
import { taguigBarangays } from '@/components/monitoring/taguigBarangays';

const MAP_WIDTH = 484;
const MAP_HEIGHT = 591;
const LON0 = 121.016;
const LON_SPAN = 0.094;
const LAT0 = 14.574;
const LAT_SPAN = 0.116;

const LAKE: Array<[number, number]> = [
    [121.092, 14.548],
    [121.09, 14.539],
    [121.084, 14.529],
    [121.077, 14.519],
    [121.07, 14.507],
    [121.063, 14.493],
    [121.058, 14.48],
    [121.052, 14.464],
    [121.098, 14.464],
    [121.098, 14.548],
];

const PLACES: Array<[string, number, number]> = [
    ['gat. andres', 121.05, 14.545],
    ['fort bonifacio', 121.05, 14.544],
    ['west rembo', 121.05, 14.562],
    ['east rembo', 121.058, 14.56],
    ['comembo', 121.057, 14.566],
    ['south cembo', 121.052, 14.553],
    ['cembo', 121.056, 14.557],
    ['pitogo', 121.064, 14.559],
    ['ususan', 121.06, 14.53],
    ['gen. r. papa', 121.056, 14.533],
    ['palar', 121.051, 14.538],
    ['napindan', 121.085, 14.542],
    ['tipas', 121.079, 14.538],
    ["taguig nat'l", 121.0715, 14.528],
    ['taguig integrated', 121.0715, 14.529],
    ['taguig science', 121.07, 14.527],
    ['upper bicutan', 121.047, 14.49],
    ['daanghari', 121.048, 14.495],
    ['maria asuncion', 121.048, 14.493],
    ['maharlika', 121.053, 14.5],
    ['signal', 121.05, 14.505],
    ['bagumbayan', 121.048, 14.48],
    ['bagong tanyag', 121.04, 14.478],
    ['wester', 121.044, 14.515],
    ['tenement', 121.053, 14.5],
];

const PIN_COLORS: Record<string, string> = {
    ex: '#178a4a',
    ok: '#7ccf97',
    na: '#f2c230',
    hp: '#ee9422',
    cr: '#e0413a',
    none: '#9aa5b1',
};

function projectX(longitude: number): number {
    return ((longitude - LON0) / LON_SPAN) * MAP_WIDTH;
}

function projectY(latitude: number): number {
    return ((LAT0 - latitude) / LAT_SPAN) * MAP_HEIGHT;
}

function points(polygon: Array<[number, number]>): string {
    return polygon.map(([longitude, latitude]) => `${projectX(longitude).toFixed(1)},${projectY(latitude).toFixed(1)}`).join(' ');
}

function inside(longitude: number, latitude: number, polygon: Array<[number, number]>): boolean {
    let insidePolygon = false;

    for (let index = 0, previous = polygon.length - 1; index < polygon.length; previous = index++) {
        const current = polygon[index];
        const prior = polygon[previous];

        if (
            current[1] > latitude !== prior[1] > latitude &&
            longitude < ((prior[0] - current[0]) * (latitude - current[1])) / (prior[1] - current[1]) + current[0]
        ) {
            insidePolygon = !insidePolygon;
        }
    }

    return insidePolygon;
}

function fraction(value: number): number {
    return value - Math.floor(value);
}

function pinPosition(school: SchoolRow): [number, number] {
    const place = PLACES.find((entry) => school.name.toLowerCase().includes(entry[0]));
    const jitterX = fraction(Math.sin(school.id * 12.9898) * 43758.5453) - 0.5;
    const jitterY = fraction(Math.sin(school.id * 78.233) * 12345.6789) - 0.5;
    let longitude = place ? place[1] + jitterX * 0.006 : 121.045 + 0.03 * (jitterX + 0.5);
    let latitude = place ? place[2] + jitterY * 0.005 : 14.495 + 0.045 * (jitterY + 0.5);

    if (!insideBarangays(longitude, latitude)) {
        longitude = 121.058 + jitterX * 0.01;
        latitude = 14.52 + jitterY * 0.01;
    }

    return [projectX(longitude), projectY(latitude)];
}

function insideBarangays(longitude: number, latitude: number): boolean {
    return taguigBarangays.some((barangay) => barangay.rings.some((ring) => inside(longitude, latitude, ring)));
}

function barangayPolygons(withTitle: boolean): string {
    return taguigBarangays
        .flatMap((barangay) =>
            barangay.rings.map((ring) => {
                const title = withTitle ? `<title>${escapeXml(barangay.name)}</title>` : '';

                return `<polygon points="${points(ring)}">${title}</polygon>`;
            }),
        )
        .join('');
}

function escapeXml(value: string): string {
    return value.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
}

function label(x: number, y: number, text: string, extra = ''): string {
    return `<text x="${x}" y="${y}" font-size="11.5" font-weight="600" fill="#5a6f86" ${extra}>${text}</text>`;
}

export function taguigMapSvg(schools: SchoolRow[]): string {
    const placed = schools.map((school) => ({ school, position: pinPosition(school) }));
    const cell = 4;
    const minX = 0;
    const maxX = MAP_WIDTH;
    const minY = 0;
    const maxY = MAP_HEIGHT;
    let zones = '';

    for (let y = Math.floor(minY / cell) * cell; y < maxY; y += cell) {
        let run: { x: number; level: string | null; width: number } | null = null;

        for (let x = Math.floor(minX / cell) * cell; x <= maxX; x += cell) {
            const centerX = x + cell / 2;
            const centerY = y + cell / 2;
            const longitude = LON0 + (centerX / MAP_WIDTH) * LON_SPAN;
            const latitude = LAT0 - (centerY / MAP_HEIGHT) * LAT_SPAN;
            let level: string | null = null;

            if (insideBarangays(longitude, latitude) && placed.length > 0) {
                let best = Number.POSITIVE_INFINITY;

                for (const pin of placed) {
                    const distance = (pin.position[0] - centerX) ** 2 + (pin.position[1] - centerY) ** 2;

                    if (distance < best) {
                        best = distance;
                        level = pin.school.flp_level;
                    }
                }
            }

            if (run && run.level === level) {
                run.width += cell;
            } else {
                if (run?.level && levelColors[run.level]) {
                    zones += `<rect x="${run.x}" y="${y}" width="${run.width + 0.6}" height="${cell + 0.6}" fill="${levelColors[run.level]}"/>`;
                }

                run = { x, level, width: cell };
            }
        }

        if (run?.level && levelColors[run.level]) {
            zones += `<rect x="${run.x}" y="${y}" width="${run.width + 0.6}" height="${cell + 0.6}" fill="${levelColors[run.level]}"/>`;
        }
    }

    const pins = placed
        .map(({ school, position }) => {
            const x = position[0].toFixed(1);
            const y = position[1].toFixed(1);
            const rate = school.reach_rate === null ? '—' : `${school.reach_rate.toFixed(1)}%`;
            const title = escapeXml(`${school.name} · ${rate} · ${school.status_label} · ${school.flp_level}`);

            return `<g><title>${title}</title><circle cx="${x}" cy="${y}" r="6.5" fill="#fff" stroke="rgba(0,0,0,.25)" stroke-width=".8"/><circle cx="${x}" cy="${y}" r="3.6" fill="${PIN_COLORS[school.status] ?? PIN_COLORS.none}"/></g>`;
        })
        .join('');

    const halo = 'stroke="#fff" stroke-width="3" paint-order="stroke" stroke-linejoin="round"';
    const kilometers = MAP_WIDTH / LON_SPAN / 111.32 / Math.cos((14.52 * Math.PI) / 180);
    const scaleX = 16;
    const scaleY = MAP_HEIGHT - 22;

    const outlines = barangayPolygons(false);
    const namedOutlines = barangayPolygons(true);

    return `<svg viewBox="0 0 ${MAP_WIDTH} ${MAP_HEIGHT}" role="img" aria-label="Map of Taguig City with barangay borders, FLP levels by area, and school pins" class="h-auto w-full">
        <defs><clipPath id="taguig-outline">${outlines}</clipPath><filter id="taguig-blur" x="-5%" y="-5%" width="110%" height="110%"><feGaussianBlur stdDeviation="1.6"/></filter></defs>
        <rect width="${MAP_WIDTH}" height="${MAP_HEIGHT}" fill="#e7f3fb"/>
        <polygon points="${points(LAKE)}" fill="#a8cdeb"/>
        <g clip-path="url(#taguig-outline)"><g filter="url(#taguig-blur)">${zones}</g></g>
        <g fill="none" stroke-linejoin="round" stroke-linecap="round">
            <g stroke="#ffffff" stroke-width="2.6">${outlines}</g>
            <g stroke="#12304f" stroke-width="1.05">${namedOutlines}</g>
        </g>
        ${label(14, 44, 'Makati')}${label(10, 300, 'Parañaque')}${label(150, 548, 'Muntinlupa')}${label(268, 34, 'Pasig')}${label(214, 64, 'Pateros')}
        <text x="292" y="405" font-size="12" font-style="italic" text-anchor="middle" fill="#3d78a8">Laguna de Bay</text>
        <text x="${projectX(121.0665)}" y="${projectY(14.511)}" font-size="11" font-weight="600" fill="#12304f" ${halo}>Lower Bicutan</text>
        <text x="${projectX(121.063)}" y="${projectY(14.537)}" font-size="11" font-weight="600" fill="#12304f" ${halo}>Pinagsama</text>
        <text x="${projectX(121.025)}" y="${projectY(14.525)}" font-size="11" font-weight="600" fill="#12304f" ${halo}>Tuktukan</text>
        <text x="${projectX(121.05)}" y="${projectY(14.4745)}" font-size="14" font-weight="800" text-anchor="middle" fill="#12304f" ${halo}>Taguig</text>
        ${pins}
        <g transform="translate(${MAP_WIDTH - 34},${MAP_HEIGHT - 52})"><circle r="15" fill="#fff" stroke="rgba(0,0,0,.2)"/><path d="M0-10L5 6 0 3-5 6z" fill="#1b3f94"/><text y="-17" font-size="10" font-weight="800" text-anchor="middle" fill="#12304f">N</text></g>
        <g font-size="10" font-weight="600"><rect x="${scaleX}" y="${scaleY}" width="${kilometers}" height="4" fill="#12304f"/><rect x="${scaleX + kilometers}" y="${scaleY}" width="${kilometers}" height="4" fill="#fff" stroke="#12304f" stroke-width=".8"/><rect x="${scaleX + 2 * kilometers}" y="${scaleY}" width="${kilometers}" height="4" fill="#12304f"/>
        ${[0, 1, 2, 3].map((step) => `<text x="${scaleX + step * kilometers}" y="${scaleY - 4}" text-anchor="middle" fill="#12304f" ${halo}>${step}</text>`).join('')}<text x="${scaleX + 3 * kilometers + 8}" y="${scaleY + 5}" fill="#12304f" ${halo}>km</text></g>
    </svg>`;
}
