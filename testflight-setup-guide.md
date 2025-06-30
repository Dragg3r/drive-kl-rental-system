# TestFlight Setup Guide for Drive KL Rental System

## Apple ID Configuration
**Your Apple ID**: m.aqibnadeem@live.com
**TestFlight Access**: ✅ Already configured

## Step 1: Apple Developer Account Setup

### 1.1 Enroll in Apple Developer Program
- Visit: https://developer.apple.com/
- Sign in with: m.aqibnadeem@live.com
- Enroll in Developer Program ($99/year)
- Complete enrollment process

### 1.2 Create App in App Store Connect
1. Go to: https://appstoreconnect.apple.com/
2. Sign in with: m.aqibnadeem@live.com
3. Click "My Apps" → "+" → "New App"
4. Fill in details:
   - **App Name**: Drive KL Rental System by Akib
   - **Bundle ID**: com.drivekl.rental
   - **Platform**: iOS
   - **Language**: English
   - **SKU**: DRIVEKL001

## Step 2: App Store Connect API Key Setup

### 2.1 Generate API Key
1. In App Store Connect → Users and Access → Keys
2. Click "Generate API Key"
3. **Name**: Codemagic Build Access
4. **Access**: App Manager
5. Download the .p8 file
6. Note down:
   - **Key ID**: (8-character string)
   - **Issuer ID**: (UUID format)

### 2.2 Required Information for Codemagic
You'll need these values:
- **Apple ID**: m.aqibnadeem@live.com
- **Team ID**: (Find in Developer Account → Membership)
- **App Store Connect App ID**: (From app details page)
- **API Key File**: The .p8 file you downloaded
- **Key ID**: From API key creation
- **Issuer ID**: From API key creation

## Step 3: Codemagic Configuration

### 3.1 Upload TestFlight Configuration
Use the **`codemagic-testflight.yaml`** configuration I created, which includes:
- ✅ Your Apple ID: m.aqibnadeem@live.com
- ✅ Bundle ID: com.drivekl.rental
- ✅ Automatic TestFlight upload
- ✅ Email notifications to your Apple ID

### 3.2 Set Up Codemagic Integrations
1. **In Codemagic Dashboard:**
   - Go to Teams → Integrations
   - Add "App Store Connect" integration
   - Upload your .p8 API key file
   - Enter Key ID and Issuer ID

2. **Create Environment Group:**
   - Name: `app_store_credentials`
   - Add variables:
     - `APP_STORE_CONNECT_APP_ID`: Your app ID from App Store Connect
     - `APPLE_DEVELOPER_TEAM_ID`: Your team ID

## Step 4: TestFlight Build Process

### 4.1 Automatic Build Flow
When you push code to GitHub main branch:
1. **Codemagic detects changes**
2. **Builds web app** (`npm run build`)
3. **Syncs Capacitor** with iOS project
4. **Fetches signing certificates** automatically
5. **Builds iOS app** with proper signing
6. **Uploads to TestFlight** automatically
7. **Sends email notification** to m.aqibnadeem@live.com

### 4.2 Testing Your App
1. **Install TestFlight** on your iPhone from App Store
2. **Accept beta invitation** (sent to m.aqibnadeem@live.com)
3. **Download and test** your Drive KL Rental app
4. **Provide feedback** through TestFlight

## Step 5: App Information Setup

### 5.1 Complete App Store Connect Details
In App Store Connect, add:
- **App Description**: Car rental management system for Drive KL
- **Keywords**: car rental, vehicle booking, Malaysia
- **Support URL**: Your website or contact info
- **Privacy Policy URL**: Required for App Store
- **App Category**: Business or Travel

### 5.2 Required App Assets
- **App Icon**: 1024x1024px (already configured with AK13 logo)
- **Screenshots**: iPhone screenshots of your app
- **App Preview Video**: Optional but recommended

## Step 6: Version Management

### 6.1 Automatic Version Increment
The configuration automatically:
- **Increments build numbers** for each TestFlight build
- **Maintains version consistency** across builds
- **Tracks build history** in TestFlight

### 6.2 Release Management
- **TestFlight**: Automatic upload after successful build
- **App Store**: Manual submission when ready
- **Beta Testing**: Up to 10,000 external testers via TestFlight

## Step 7: Troubleshooting

### 7.1 Common Issues
- **Certificate Problems**: Ensure Apple Developer account is active
- **Bundle ID Conflicts**: Verify com.drivekl.rental is unique
- **API Key Issues**: Check Key ID and Issuer ID are correct

### 7.2 Build Monitoring
- **Email Notifications**: Sent to m.aqibnadeem@live.com
- **Build Logs**: Available in Codemagic dashboard
- **TestFlight Status**: Check in App Store Connect

## Expected Timeline

### Initial Setup: 1-2 hours
- Apple Developer enrollment
- App Store Connect setup
- Codemagic configuration

### First Build: 20-30 minutes
- Automatic after GitHub push
- TestFlight availability: Additional 10-15 minutes

### Testing: Immediate
- Download via TestFlight
- Install on iPhone
- Test all features

## Next Steps After Setup

1. **Complete Apple Developer enrollment**
2. **Create app in App Store Connect**
3. **Generate API key and upload to Codemagic**
4. **Push code to GitHub with TestFlight configuration**
5. **Monitor first build and TestFlight upload**
6. **Test app on iPhone via TestFlight**

Your Drive KL Rental System will be available for testing on iPhone within 30 minutes of pushing code to GitHub!