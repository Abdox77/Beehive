import { Component, ViewContainerRef, ComponentRef } from '@angular/core';
import * as L from 'leaflet';
import { HttpClient } from '@angular/common/http';
import { Intervention } from '../intervention/intervention';

interface Hive {
    id: number;
    name: string;
    lat: number;
    lng: number;
}

const BACKEND_URL = 'http://localhost:8000';


@Component({
  selector: 'app-map',
  imports: [ ],
  templateUrl: './map.html',
  styleUrl: './map.css'
})
export class Map {
    private map: any;
    private markers: L.Marker[] = [];
    private hives: Hive[] = [];

    constructor(
        private http: HttpClient,
        private vrc: ViewContainerRef
    ) 
    { }

    ngOnInit(): void {
        this.loadHives();
    }

    ngAfterViewInit(): void {
        this.initMap();
    }

    private initMap(): void{
        this.map = L.map('map').setView([49.845732, 3.262939], 10);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(this.map);

        if (this.hives.length > 0) {
            this.addHiveMarkers();
        }
    }

    private loadHives(): void {
        const token = localStorage.getItem('token');
        this.http.get<{ success: boolean; hives: Hive[] }>(`${BACKEND_URL}/api/hive`, {
            headers: { Authorization: `Bearer ${token}` }
        }).subscribe({
            next: (response) => {
                this.hives = response.hives;
                if (this.map) {
                    this.addHiveMarkers();
                }
            },
            error:(error) => console.error('Error loading hives: ', error)
        });
    }

    private addHiveMarkers(): void {
        this.markers.forEach(marker => this.map.removeLayer(marker));
        this.markers = [];

        const hiveIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        this.hives.forEach(hive => {
            const marker = L.marker([hive.lat, hive.lng], { icon: hiveIcon })
                .addTo(this.map);

                let componentRef : ComponentRef<Intervention> | null = null;
                marker.bindPopup(() => {
                    componentRef = this.vrc.createComponent(Intervention);
                    componentRef.instance.hiveId = hive.id;
                    componentRef.instance.closePopup.subscribe(() => {
                        marker.closePopup();
                    });
                    componentRef.changeDetectorRef.detectChanges();
                    return componentRef.location.nativeElement;
                },
                {
                    minWidth: 550, 
                    maxWidth: 550,
                    offset: [0, -20],
                    closeButton: false
                });

                marker.on('popupclose', () => {
                    if (componentRef)
                    {
                        componentRef.destroy();
                        componentRef = null;
                    }
                });
                this.markers.push(marker);
        });

        if (this.markers.length > 0) {
            const group = L.featureGroup(this.markers);
            this.map.fitBounds(group.getBounds().pad(0.1));
        }
    }

    public refreshHives(): void {
        this.loadHives();
    }
}


