-- FusionPBX Webhook IVR action

local json = require "resources.functions.lunajson"
local Database = require "resources.functions.database"

local option_uuid = argv[1]
if option_uuid == nil or option_uuid == '' then
return
end

local domain_uuid = session:getVariable('domain_uuid') or ''
local ivr_menu_uuid = session:getVariable('ivr_menu_uuid') or ''
local webhook_ivr_call_uuid = session:getVariable('webhook_ivr_call_uuid') or ''
local webhook_ivr_reference = session:getVariable('webhook_ivr_reference') or ''
local call_uuid = session:getVariable('uuid') or ''
local caller_id_number = session:getVariable('caller_id_number') or ''
local destination_number = session:getVariable('destination_number') or ''

local dbh = Database.new('system')
assert(dbh:connected())

local option = {}
local sql = "select o.ivr_menu_option_digits, w.webhook_url, w.webhook_secret, w.response_recording, w.failed_recording, w.next_action_app, w.next_action_data, w.webhook_enabled "
.."from v_ivr_menu_options o left join v_ivr_menu_option_webhooks w on w.ivr_menu_option_uuid = o.ivr_menu_option_uuid and w.domain_uuid = o.domain_uuid "
.."where o.domain_uuid = :domain_uuid and o.ivr_menu_option_uuid = :option_uuid limit 1"
dbh:query(sql, {domain_uuid = domain_uuid, option_uuid = option_uuid}, function(row)
option = row
end)

if option.ivr_menu_option_digits == nil then
return
end

if webhook_ivr_call_uuid ~= '' then
dbh:query("update v_webhook_ivr_calls set call_status = 'completed', update_date = now() where domain_uuid = :domain_uuid and webhook_ivr_call_uuid = :webhook_ivr_call_uuid", {
domain_uuid = domain_uuid,
webhook_ivr_call_uuid = webhook_ivr_call_uuid
})
end

local payload = {
event = "ivr.option.selected",
reference = webhook_ivr_reference,
digits = option.ivr_menu_option_digits,
call_uuid = call_uuid,
webhook_ivr_call_uuid = webhook_ivr_call_uuid,
ivr_menu_uuid = ivr_menu_uuid,
ivr_menu_option_uuid = option_uuid,
caller_id_number = caller_id_number,
destination_number = destination_number,
timestamp = os.date("!%Y-%m-%dT%H:%M:%SZ")
}

local payload_json = json.encode(payload)
local api = freeswitch.API()
local webhook_ivr_event_uuid = api:executeString("create_uuid")

dbh:query("insert into v_webhook_ivr_events (webhook_ivr_event_uuid, domain_uuid, ivr_menu_uuid, ivr_menu_option_uuid, webhook_ivr_call_uuid, call_uuid, reference, digits, webhook_url, webhook_secret, payload_json, event_status, attempts, max_attempts, scheduled_at, insert_date) values (:webhook_ivr_event_uuid, :domain_uuid, :ivr_menu_uuid, :ivr_menu_option_uuid, :webhook_ivr_call_uuid, :call_uuid, :reference, :digits, :webhook_url, :webhook_secret, :payload_json, 'queued', 0, 5, now(), now())", {
webhook_ivr_event_uuid = webhook_ivr_event_uuid,
domain_uuid = domain_uuid,
ivr_menu_uuid = ivr_menu_uuid,
ivr_menu_option_uuid = option_uuid,
webhook_ivr_call_uuid = webhook_ivr_call_uuid,
call_uuid = call_uuid,
reference = webhook_ivr_reference,
digits = option.ivr_menu_option_digits,
webhook_url = option.webhook_url or '',
webhook_secret = option.webhook_secret or '',
payload_json = payload_json
})

if option.response_recording ~= nil and option.response_recording ~= '' then
session:execute('playback', option.response_recording)
end

local action = option.next_action_app or 'hangup'
local data = option.next_action_data or ''
if action == 'transfer' and data ~= '' then
local context = session:getVariable('context') or session:getVariable('domain_name') or ''
session:execute('transfer', data .. ' XML ' .. context)
elseif action == 'playback' and data ~= '' then
session:execute('playback', data)
else
session:execute('hangup', '')
end
