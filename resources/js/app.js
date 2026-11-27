import Alpine from 'alpinejs';
import nuevaRemision from './remision';
import filtroEnVivo from './filtro-en-vivo';
import exportarRemisiones from './exportar-remisiones';

window.Alpine = Alpine;

Alpine.data('nuevaRemision', nuevaRemision);
Alpine.data('filtroEnVivo', filtroEnVivo);
Alpine.data('exportarRemisiones', exportarRemisiones);

Alpine.start();
