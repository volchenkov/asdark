@extends('layout')

@section('content')
<div id="app">
    <div class="row">
        <div class="col-md-8 form-group">
            <p>Ознакомьтесь с <a href="/help#caveats" target="_blank">ограничениями редактирования</a> и <a href="/help#editable-fields" target="_blank">списком редактируемых полей</a></p>

            <v-select :options="clients"
                      label="name"
                      required
                      @input="fetchCampaigns"
                      placeholder="Выберите клиента"
                      :disabled="sid !== null || loadingFeed"
                      :reduce="client => client.id"
                      v-model="selectedClient">

                <template #no-options="{ search, searching, loading }">
                    Ничего не найдено
                </template>
            </v-select>
        </div>

        <div class="col-md-8 form-group" v-if="loading">
            <div class="text-center my-3" >
                <div class="spinner-border text-secondary" role="status" title="Загрузка кампаний из ВК">
                    <span class="sr-only">Загрузка...</span>
                </div>
            </div>
        </div>

        <div class="col-md-8 form-group" v-if="campaignsLoaded">
            <p v-if="campaigns.length == 0">Кампании клиента не найдены</p>
            <v-select :options="campaigns"
                      label="name"
                      :close-on-select="false"
                      v-if="campaigns.length > 0"
                      required
                      multiple
                      :select-on-key-codes="[13]"
                      :disabled="sid !== null || loadingFeed"
                      placeholder="Выберите кампании"
                      :reduce="campaign => campaign.id"
                      v-model="selectedCampaigns">

                <template #no-options="{ search, searching, loading }">
                    Ничего не найдено
                </template>
            </v-select>
        </div>
        <div class="col-md-8 form-group text-danger" v-if="campaignsLoadingError">
            <div><strong>Не удалось загрузить кампании клиента 😞 </strong></div>
            <div>Попробуйте снова, если это не впервые - обратитесь к администратору.</div>
        </div>
    </div>

    <div class="row" v-if="loadingFeedError">
        <div class="col-md-8 form-group text-danger">
            <div class="alert alert-warning">
                <div><strong>Не удалось создать таблицу с объявлениями 😞 </strong></div>
                <div>Попробуйте снова, если это не впервые - обратитесь к администратору.</div>
            </div>
        </div>
    </div>

    <div class="row mb-5" v-if="campaignsLoaded && !(loadingFeed || sid)">
        <div class="col-md-8">
            <button class="btn btn-primary"
                    :disabled="!selectedCampaigns || selectedCampaigns.length == 0"
                    @click="generateFeed"
                    type="button">далее</button>
        </div>
    </div>


    <div class="col-md-8 form-group" v-if="loadingFeed">
        <div class="text-center my-3" >
            <div class="spinner-border text-secondary" role="status" title="Сбор объявлений из ВК">
                <span class="sr-only">Загрузка...</span>
            </div>
        </div>
    </div>


    <div class="row mb-2" v-if="sid !== null">
        <div class="col-12 mb-3">
            <div>Таблица объявлений создана и доступна для редактирования. Одна строка - одно объявление:</div>
            <p>
                <strong><a :href="'https://docs.google.com/spreadsheets/d/'+sid" target="_blank">https://docs.google.com/spreadsheets/d/@{{sid}}</a></strong>
            </p>
            <p>После редактирования таблицы воспользуйтесь кнопкой ниже:</p>
        </div>

        <div class="col-12">
            <form action="{{ route('export.start') }}" method="POST">
                @csrf
                <input type="hidden" name="spreadsheetId" :value="sid">
                <button class="btn btn-primary" type="submit">загрузить в ВК</button>
                <button class="btn btn-light" type="reset" @click="sid = null">назад</button>
            </form>
        </div>
    </div>

</div>

<script type="text/javascript">

    function addQueryParams(url, params) {
        let esc = encodeURIComponent;
        return url + '?' + Object.keys(params)
            .map(k => esc(k) + '=' + esc(params[k]))
            .join('&');
    }

    let app = new Vue({
        el: '#app',
        methods: {
            generateFeed() {
                this.loadingFeed = true;
                this.loadingFeedError = false;

                let url = addQueryParams('/ads_edit_generate', {
                    client_id: this.selectedClient,
                    campaign_ids: this.selectedCampaigns.join(',')
                });
                fetch(url)
                    .then(response => {
                        if (response.ok) {
                            response.text()
                                .then(sid => {
                                    this.sid = sid;
                                })
                        } else {
                            console.error(response);
                            this.loadingFeedError = true;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.loadingFeedError = true;
                    })
                    .finally(() => {
                        this.loadingFeed = false;
                    });
            },
            fetchCampaigns(clientId) {
                if (clientId === null) {
                    this.campaigns = [];
                    this.selectedCampaigns = [];
                    this.campaignsLoaded = false;

                    return;
                }
                this.campaignsLoadingError = false;
                this.loading = true;

                fetch('/ads_edit_get_campaigns')
                    .then(response => {
                        if (response.ok) {
                            response.json()
                                .then(campaigns => {
                                    campaigns.sort((a, b) => a.name > b.name ? -1 : (a.name === b.name ? 0 : 1));
                                    this.campaigns = campaigns;
                                    this.campaignsLoaded = true;
                                })
                        } else {
                            console.error(response);
                            this.campaignsLoadingError = true;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.campaignsLoadingError = true;
                    })
                    .finally(() => {
                        this.loading = false;
                    })
            }
        },
        data: {
            loading: false,
            loadingFeed: false,
            loadingFeedError: false,
            campaigns: [],
            clients: @json($clients),
            campaignsLoaded: false,
            campaignsLoadingError: false,
            selectedClient: null,
            selectedCampaigns: null,
            sid: null
        }
    })
</script>

@endsection
