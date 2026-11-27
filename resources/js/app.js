import Alpine from 'alpinejs';
import nuevaRemision from './remision';
import filtroEnVivo from './filtro-en-vivo';
import exportarRemisiones from './exportar-remisiones';
import miles from './miles';
import recordarIngreso from './recordar-ingreso';

window.Alpine = Alpine;

Alpine.data('nuevaRemision', nuevaRemision);
Alpine.data('filtroEnVivo', filtroEnVivo);
Alpine.data('exportarRemisiones', exportarRemisiones);
Alpine.data('recordarIngreso', recordarIngreso);
Alpine.directive('miles', miles);

Alpine.start();
