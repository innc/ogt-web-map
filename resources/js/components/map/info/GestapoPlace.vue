<template>
    <div>
        <!-- inception - https://www.wikidata.org/wiki/Property:P571 -->
        <!-- dissolved, abolished or demolished date - https://www.wikidata.org/wiki/Property:P576 -->
        <template v-if="selectedPlace.inceptionDate.value || selectedPlace.dissolvedDate.value">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Laufzeit</v-list-item-title>
                    <v-list-item-subtitle>
                        <template v-if="selectedPlace.inceptionDate.value">
                            von {{ selectedPlace.inceptionDate.locale }}
                        </template>
                        <template v-if="selectedPlace.dissolvedDate.value">
                            bis {{ selectedPlace.dissolvedDate.locale }}
                        </template>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <address-info
            :selectedPlace="selectedPlace"
            @switchLocation="$emit('switchLocation', $event)"
            @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
            @zoomIntoPlace="$emit('zoomIntoPlace')"
        ></address-info>

        <!-- number of  employees (point in time, sourcing circumstances) - https://www.wikidata.org/wiki/Property:P1128
             director / manager (time period) - https://www.wikidata.org/wiki/Property:P1037
             employer - https://www.wikidata.org/wiki/Property:P108
        -->
        <template v-if="selectedPlace.employeeCounts.length > 0 ||
                        selectedPlace.directors.length > 0 ||
                        selectedPlace.employees.length > 0"
        >
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Personalstärke</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul v-if="selectedPlace.employeeCounts.length > 0">
                            <li v-for="employeeCount in selectedPlace.employeeCounts">
                                <template v-if="employeeCount.sourcingCircumstance">
                                    {{ employeeCount.sourcingCircumstance }}
                                </template>
                                {{ employeeCount.value }}
                                <template v-if="employeeCount.pointInTime">
                                    ({{ employeeCount.pointInTime.locale }})
                                </template>
                            </li>
                        </ul>
                        <div v-if="selectedPlace.directors.length > 0" class="mt-2">
                            Leitung
                            <ul>
                                <li v-for="director in selectedPlace.directors" class="mb-2">
                                    <a v-if="director.hasPersonData"
                                       @click.stop="$emit('showPerson', {
                                            id: director.id,
                                            group: 'perpetrators',
                                        })"
                                       href="#"
                                    >
                                        {{ director.name }}
                                    </a>
                                    <template v-else>
                                        {{ director.name }}
                                    </template>
                                    <ul v-if="director.startDate || director.endDate">
                                        <li class="white-space-normal">
                                            <template v-if="director.startDate && director.maxStartDate">
                                                von zwischen {{ director.startDate.locale }} und
                                                {{ director.maxStartDate.locale }}
                                            </template>
                                            <template v-else-if="director.startDate">
                                                von {{ director.startDate.locale }}
                                            </template>
                                            <template v-if="director.minEndDate && director.endDate">
                                                bis zwischen {{ director.minEndDate.locale }} und
                                                {{ director.endDate.locale }}
                                            </template>
                                            <template v-else-if="director.endDate">
                                                bis {{ director.endDate.locale }}
                                            </template>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                        <div v-if="selectedPlace.employees.length > 0" class="my-2">
                            Mitarbeitende
                            <ul>
                                <li v-for="employee in selectedPlace.employees" class="mb-2">
                                    <a
                                        @click.stop="$emit('showPerson', {
                                            id: employee.id,
                                            group: 'perpetrators',
                                        })"
                                        href="#"
                                    >
                                        {{ employee.name }}
                                    </a>
                                    <ul v-if="employee.startDate || employee.endDate">
                                        <li class="white-space-normal">
                                            <template v-if="employee.startDate && employee.maxStartDate">
                                                von zwischen {{ employee.startDate.locale }} und
                                                {{ employee.maxStartDate.locale }}
                                            </template>
                                            <template v-else-if="employee.startDate">
                                                von {{ employee.startDate.locale }}
                                            </template>
                                            <template v-if="employee.minEndDate && employee.endDate">
                                                bis zwischen {{ employee.minEndDate.locale }} und
                                                {{ employee.endDate.locale }}
                                            </template>
                                            <template v-else-if="employee.endDate">
                                                bis {{ employee.endDate.locale }}
                                            </template>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <prisoners
            :selectedPlace="selectedPlace"
            @showPerson="$emit('showPerson', $event)"
        ></prisoners>

        <significant-event
            v-if="selectedPlace.significantEvents.length > 0"
            :selectedPlace="selectedPlace"
            @switchLocation="$emit('switchLocation', $event)"
        ></significant-event>

        <!-- parent organizations (time period) - https://www.wikidata.org/wiki/Property:P749 -->
        <template v-if="selectedPlace.parentOrganizations.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Übergeordnete Organisation</v-list-item-title>
                    <v-list-item-subtitle class="white-space-normal">
                        <div v-for="parentOrganization in selectedPlace.parentOrganizations" class="mb-3">
                            <a v-if="parentOrganization.hasLocationMarker"
                               @click.stop="$emit('switchLocation', {
                                   locationId: parentOrganization.id,
                               })"
                               href="#"
                            >
                                {{ parentOrganization.label }}
                            </a>
                            <template v-else>
                                {{ parentOrganization.label }}
                            </template>
                            <ul v-if="parentOrganization.startDate || parentOrganization.endDate">
                                <li>
                                    <template v-if="parentOrganization.startDate">
                                        von {{ parentOrganization.startDate.locale }}
                                    </template>
                                    <template v-if="parentOrganization.endDate">
                                        bis {{ parentOrganization.endDate.locale }}
                                    </template>
                                </li>
                            </ul>
                        </div>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- child organizations (time period) - https://www.wikidata.org/wiki/Property:P355 -->
        <template v-if="selectedPlace.childOrganizations.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Nachgeordnete Organisation</v-list-item-title>
                    <v-list-item-subtitle class="white-space-normal">
                        <div v-for="childOrganization in selectedPlace.childOrganizations" class="mb-3">
                            <a v-if="childOrganization.hasLocationMarker"
                               @click.stop="$emit('switchLocation', {
                                   locationId: childOrganization.id,
                               })"
                               href="#"
                            >
                                {{ childOrganization.label }}
                            </a>
                            <template v-else>
                                {{ childOrganization.label }}
                            </template>
                            <ul v-if="childOrganization.startDate || childOrganization.endDate">
                                <li>
                                    <template v-if="childOrganization.startDate">
                                        von {{ childOrganization.startDate.locale }}
                                    </template>
                                    <template v-if="childOrganization.endDate">
                                        bis {{ childOrganization.endDate.locale }}
                                    </template>
                                </li>
                            </ul>
                        </div>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- replaces (point in time) - https://www.wikidata.org/wiki/Property:P1365 -->
        <template v-if="selectedPlace.predecessors.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Vorgängerorganisation (zeitlich)</v-list-item-title>
                    <v-list-item-subtitle class="white-space-normal">
                        <div v-for="predecessor in selectedPlace.predecessors" class="mb-3">
                            <a v-if="predecessor.hasLocationMarker"
                               @click.stop="$emit('switchLocation', {
                                   locationId: predecessor.id,
                               })"
                               href="#"
                            >
                                {{ predecessor.label }}
                            </a>
                            <div v-else>
                                {{ predecessor.label }}
                            </div>
                            <ul v-if="predecessor.pointInTime">
                                <li>
                                    ab {{ predecessor.pointInTime.locale }}
                                </li>
                            </ul>
                        </div>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- replaced by (point in time) - https://www.wikidata.org/wiki/Property:P1366 -->
        <template v-if="selectedPlace.successors.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Nachfolgeorganisation (zeitlich)</v-list-item-title>
                    <v-list-item-subtitle class="white-space-normal">
                        <div v-for="successor in selectedPlace.successors" class="mb-3">
                            <a v-if="successor.hasLocationMarker"
                               @click.stop="$emit('switchLocation', {
                                   locationId: successor.id,
                               })"
                               href="#"
                            >
                                {{ successor.label }}
                            </a>
                            <div v-else>
                                {{ successor.label }}
                            </div>
                            <ul v-if="successor.pointInTime">
                                <li>
                                    ab {{ successor.pointInTime.locale }}
                                </li>
                            </ul>
                        </div>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- location commemorated by -->
        <template v-if="selectedPlace.commemoratedBy.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Erinnerungsort</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul>
                            <li v-for="commemoratedBy in selectedPlace.commemoratedBy">
                                <a v-if="commemoratedBy.hasLocationMarker"
                                   @click.stop="$emit('switchLocation', {
                                           locationId: commemoratedBy.id,
                                       })"
                                   href="#"
                                >
                                    {{ commemoratedBy.name }}
                                </a>
                                <div v-else>
                                    {{ commemoratedBy.name }}
                                </div>
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <sources v-if="selectedPlace.sources.length > 0"
                 :selectedPlace="selectedPlace">
        </sources>
    </div>
</template>

<script>
import AddressInfo from './AddressInfo';
import Prisoners from './Prisoners';
import SignificantEvent from './SignificantEvent';
import Sources from './Sources';

export default {
    name: 'GestapoPlace',
    components: {AddressInfo, Prisoners, SignificantEvent, Sources},
    props: ['selectedPlace'],
};
</script>

<style scoped>
/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>