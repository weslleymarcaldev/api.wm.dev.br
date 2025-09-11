### Observaçoes:
- json comunicando com API
- CMV

### Como chamar:
GET /api.php?r=/ping
GET /api.php?r=/time
GET /api.php?r=/sum&a=2&b=3
POST /api.php?r=/echo (body JSON)
POST /api.php?r=/sum (body JSON { "a": 2, "b": 3 })

### Testes rápidos por terminal (opcional)
# GET ping
curl -s 'https://api.wm.dev.br/api.php?r=/ping' | jq

# GET soma
curl -s 'https://api.wm.dev.br/api.php?r=/sum&a=2&b=3' | jq

# POST echo
curl -s -X POST 'https://api.wm.dev.br/api.php?r=/echo' \
    -H 'Content-Type: application/json' \
    -d '{"hello":"world"}' | jq