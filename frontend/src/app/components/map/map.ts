import { Component } from '@angular/core';
import * as L from 'leaflet';


@Component({
  selector: 'app-map',
  imports: [],
  templateUrl: './map.html',
  styleUrl: './map.css'
})
export class Map {
    private map: any;

    ngAfterViewInit(): void {
        this.initMap();
    }

    private initMap(): void{
        this.map = L.map('map').setView([51.505, -0.09], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(this.map);
        
        // setTimeout(() => {
        //     this.map.invalidateSize();
        // }, 100);
    }
}
